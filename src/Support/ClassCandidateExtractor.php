<?php

namespace Mary\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class ClassCandidateExtractor
{
    /**
     * Extract literal candidates passed to Mary's internal class APIs.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    public static function fromPaths(array $paths): array
    {
        $classes = [];

        foreach (self::sourceFiles($paths) as $file) {
            $source = file_get_contents($file);

            if ($source === false) {
                throw new RuntimeException("Unable to read Mary class source [{$file}].");
            }

            foreach (self::expressions($source) as $expression) {
                foreach (self::candidateLiterals($expression) as $literal) {
                    foreach (DynamicClassCandidateResolver::expand($literal, $source, $file) as $resolved) {
                        foreach (preg_split('/\s+/', trim($resolved)) ?: [] as $class) {
                            if ($class !== '') {
                                $classes[] = $class;
                            }
                        }
                    }
                }
            }
        }

        $classes = array_values(array_unique($classes));
        sort($classes);

        return $classes;
    }

    /** @return array<int, string> */
    private static function sourceFiles(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;

                continue;
            }

            if (! is_dir($path)) {
                throw new RuntimeException("Mary class source path [{$path}] does not exist.");
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }

    /** @return array<int, string> */
    private static function expressions(string $source): array
    {
        $source = self::withoutComments($source);
        $searchSource = self::callSearchSource($source);

        preg_match_all(
            '/(?:Mary\s*::\s*classes|app\s*\(\s*[\'\"]mary[\'\"]\s*\)\s*->\s*classes|@maryClass|->\s*maryClass)\s*\(/',
            $searchSource,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $expressions = [];

        foreach ($matches[0] as [$match, $offset]) {
            $open = $offset + strlen($match) - 1;
            $call = self::parenthesized($source, $open);

            if ($call !== null) {
                [$expression, $close] = $call;
                $expressions[] = $expression;
                array_push($expressions, ...self::chainedAddExpressions($source, $close + 1));
            }
        }

        return $expressions;
    }

    private static function withoutComments(string $source): string
    {
        $source = preg_replace_callback(
            '/\{\{--.*?--\}\}|<!--.*?-->/s',
            fn (array $match): string => preg_replace('/[^\r\n]/', ' ', $match[0]) ?? $match[0],
            $source
        ) ?? $source;

        $sanitized = '';

        foreach (token_get_all($source) as $token) {
            if (! is_array($token)) {
                $sanitized .= $token;

                continue;
            }

            [$type, $contents] = $token;
            $sanitized .= in_array($type, [T_COMMENT, T_DOC_COMMENT], true)
                ? (preg_replace('/[^\r\n]/', ' ', $contents) ?? $contents)
                : $contents;
        }

        return $sanitized;
    }

    private static function callSearchSource(string $source): string
    {
        $searchable = '';
        $insideHeredoc = false;

        foreach (token_get_all($source) as $token) {
            if (! is_array($token)) {
                $searchable .= $token;

                continue;
            }

            [$type, $contents] = $token;

            if ($type === T_START_HEREDOC) {
                $insideHeredoc = true;
                $searchable .= $contents;

                continue;
            }

            if ($type === T_END_HEREDOC) {
                $insideHeredoc = false;
                $searchable .= $contents;

                continue;
            }

            $isOrdinaryString = $type === T_CONSTANT_ENCAPSED_STRING
                || ($type === T_ENCAPSED_AND_WHITESPACE && ! $insideHeredoc);
            $isMaryContainerKey = $type === T_CONSTANT_ENCAPSED_STRING
                && in_array($contents, ["'mary'", '"mary"'], true);

            $searchable .= $isOrdinaryString && ! $isMaryContainerKey
                ? (preg_replace('/[^\r\n]/', ' ', $contents) ?? $contents)
                : $contents;
        }

        return $searchable;
    }

    /** @return array{string, int}|null */
    private static function parenthesized(string $source, int $open): ?array
    {
        $depth = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = $open; $index < $length; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')' && --$depth === 0) {
                return [substr($source, $open + 1, $index - $open - 1), $index];
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private static function chainedAddExpressions(string $source, int $offset): array
    {
        $expressions = [];
        $length = strlen($source);

        while ($offset < $length) {
            while ($offset < $length && ctype_space($source[$offset])) {
                $offset++;
            }

            if (substr($source, $offset, 2) !== '->') {
                break;
            }

            $offset += 2;

            while ($offset < $length && ctype_space($source[$offset])) {
                $offset++;
            }

            if (! preg_match('/\A([A-Za-z_][A-Za-z0-9_]*)\s*\(/', substr($source, $offset), $method)) {
                break;
            }

            $open = $offset + strlen($method[0]) - 1;
            $call = self::parenthesized($source, $open);

            if ($call === null) {
                break;
            }

            [$expression, $close] = $call;

            if ($method[1] === 'add') {
                $expressions[] = $expression;
            }

            $offset = $close + 1;
        }

        return $expressions;
    }

    /** @return array<int, string> */
    private static function candidateLiterals(string $expression): array
    {
        $expression = self::unwrapParentheses(trim($expression));

        if (($coalesced = self::splitTopLevelCoalescence($expression)) !== null) {
            $literals = [];

            foreach ($coalesced as $candidate) {
                array_push($literals, ...self::candidateLiterals($candidate));
            }

            return $literals;
        }

        if (($ternary = self::topLevelTernary($expression)) !== null) {
            [$question, $colon] = $ternary;
            $whenTrue = substr($expression, $question + 1, $colon - $question - 1);

            if (trim($whenTrue) === '') {
                $whenTrue = substr($expression, 0, $question);
            }

            return [
                ...self::candidateLiterals($whenTrue),
                ...self::candidateLiterals(substr($expression, $colon + 1)),
            ];
        }

        if (($matchResults = self::matchResults($expression)) !== null) {
            $literals = [];

            foreach ($matchResults as $result) {
                array_push($literals, ...self::candidateLiterals($result));
            }

            return $literals;
        }

        $arrayContents = null;

        if (str_starts_with($expression, '[') && str_ends_with($expression, ']')) {
            $arrayContents = substr($expression, 1, -1);
        } elseif (preg_match('/\Aarray\s*\(/', $expression, $match)) {
            $open = strlen($match[0]) - 1;
            $call = self::parenthesized($expression, $open);

            if ($call !== null && $call[1] === strlen($expression) - 1) {
                $arrayContents = $call[0];
            }
        }

        if ($arrayContents === null) {
            if (preg_match('/\A\$(?:this->)?[A-Za-z_][A-Za-z0-9_]*\z/', $expression)) {
                return [$expression];
            }

            if (preg_match('/\A\$(?:this->)?[A-Za-z_][A-Za-z0-9_]*\s*\([^{}]*\)\z/s', $expression)) {
                return ['{'.$expression.'}'];
            }

            return self::literals($expression);
        }

        $literals = [];

        foreach (self::splitTopLevel($arrayContents, ',') as $item) {
            $arrow = self::topLevelArrow($item);
            $candidate = $arrow === null ? $item : substr($item, 0, $arrow);
            array_push($literals, ...self::candidateLiterals($candidate));
        }

        return $literals;
    }

    private static function unwrapParentheses(string $expression): string
    {
        while (str_starts_with($expression, '(')) {
            $call = self::parenthesized($expression, 0);

            if ($call === null || $call[1] !== strlen($expression) - 1) {
                break;
            }

            $expression = trim($call[0]);
        }

        return $expression;
    }

    /** @return array<int, string>|null */
    private static function splitTopLevelCoalescence(string $source): ?array
    {
        $parts = [];
        $start = 0;
        $round = 0;
        $square = 0;
        $curly = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = 0; $index < $length - 1; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;

                continue;
            }

            match ($character) {
                '(' => $round++,
                ')' => $round--,
                '[' => $square++,
                ']' => $square--,
                '{' => $curly++,
                '}' => $curly--,
                default => null,
            };

            if ($character === '?'
                && $source[$index + 1] === '?'
                && $round === 0
                && $square === 0
                && $curly === 0) {
                $parts[] = substr($source, $start, $index - $start);
                $start = $index + 2;
                $index++;
            }
        }

        if ($parts === []) {
            return null;
        }

        $parts[] = substr($source, $start);

        return $parts;
    }

    /** @return array<int, string>|null */
    private static function matchResults(string $expression): ?array
    {
        if (! preg_match('/\Amatch\s*\(/', $expression, $match)) {
            return null;
        }

        $open = strlen($match[0]) - 1;
        $condition = self::parenthesized($expression, $open);

        if ($condition === null) {
            return null;
        }

        $brace = $condition[1] + 1;

        while (isset($expression[$brace]) && ctype_space($expression[$brace])) {
            $brace++;
        }

        if (($expression[$brace] ?? null) !== '{'
            || ($body = self::braced($expression, $brace)) === null
            || trim(substr($expression, $body[1] + 1)) !== '') {
            return null;
        }

        $results = [];

        foreach (self::splitTopLevel($body[0], ',') as $arm) {
            if (($arrow = self::topLevelArrow($arm)) !== null) {
                $results[] = substr($arm, $arrow + 2);
            }
        }

        return $results;
    }

    /** @return array{string, int}|null */
    private static function braced(string $source, int $open): ?array
    {
        $depth = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = $open; $index < $length; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;
            } elseif ($character === '{') {
                $depth++;
            } elseif ($character === '}' && --$depth === 0) {
                return [substr($source, $open + 1, $index - $open - 1), $index];
            }
        }

        return null;
    }

    /** @return array{int, int}|null */
    private static function topLevelTernary(string $source): ?array
    {
        $round = 0;
        $square = 0;
        $curly = 0;
        $quote = null;
        $escaped = false;
        $question = null;
        $nested = 0;
        $length = strlen($source);

        for ($index = 0; $index < $length; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;

                continue;
            }

            match ($character) {
                '(' => $round++,
                ')' => $round--,
                '[' => $square++,
                ']' => $square--,
                '{' => $curly++,
                '}' => $curly--,
                default => null,
            };

            if ($round !== 0 || $square !== 0 || $curly !== 0) {
                continue;
            }

            if ($character === '?'
                && ($source[$index - 1] ?? null) !== '?'
                && ($source[$index + 1] ?? null) !== '?'
                && substr($source, $index, 3) !== '?->') {
                if ($question === null) {
                    $question = $index;
                } else {
                    $nested++;
                }

                continue;
            }

            if ($question !== null
                && $character === ':'
                && ($source[$index - 1] ?? null) !== ':'
                && ($source[$index + 1] ?? null) !== ':') {
                if ($nested > 0) {
                    $nested--;

                    continue;
                }

                return [$question, $index];
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private static function splitTopLevel(string $source, string $separator): array
    {
        $parts = [];
        $start = 0;
        $round = 0;
        $square = 0;
        $curly = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = 0; $index < $length; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;

                continue;
            }

            match ($character) {
                '(' => $round++,
                ')' => $round--,
                '[' => $square++,
                ']' => $square--,
                '{' => $curly++,
                '}' => $curly--,
                default => null,
            };

            if ($character === $separator && $round === 0 && $square === 0 && $curly === 0) {
                $parts[] = substr($source, $start, $index - $start);
                $start = $index + 1;
            }
        }

        $parts[] = substr($source, $start);

        return $parts;
    }

    private static function topLevelArrow(string $source): ?int
    {
        $round = 0;
        $square = 0;
        $curly = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = 0; $index < $length - 1; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;

                continue;
            }

            match ($character) {
                '(' => $round++,
                ')' => $round--,
                '[' => $square++,
                ']' => $square--,
                '{' => $curly++,
                '}' => $curly--,
                default => null,
            };

            if ($character === '=' && $source[$index + 1] === '>' && $round === 0 && $square === 0 && $curly === 0) {
                return $index;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private static function literals(string $expression): array
    {
        $literals = [];
        $length = strlen($expression);

        // Nested literals are intentionally included. False positives only add
        // CSS, while skipping a nested candidate can leave a component unstyled.
        for ($index = 0; $index < $length; $index++) {
            $character = $expression[$index];

            if ($character !== '\'' && $character !== '"') {
                continue;
            }

            $quote = $character;
            $literal = '';
            $escaped = false;

            for ($index++; $index < $length; $index++) {
                $character = $expression[$index];

                if ($escaped) {
                    $literal .= '\\'.$character;
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    break;
                } else {
                    $literal .= $character;
                }
            }

            $literals[] = $quote === '\''
                ? str_replace(['\\\\', "\\'"], ['\\', "'"], $literal)
                : stripcslashes($literal);
        }

        return $literals;
    }
}
