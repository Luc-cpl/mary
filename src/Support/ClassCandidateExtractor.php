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
        preg_match_all(
            '/(?:Mary::classes|app\([\'\"]mary[\'\"]\)->classes|@maryClass|->maryClass)\s*\(/',
            $source,
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
        $expression = trim($expression);

        if (! str_starts_with($expression, '[') || ! str_ends_with($expression, ']')) {
            return self::literals($expression);
        }

        $literals = [];

        foreach (self::splitTopLevel(substr($expression, 1, -1), ',') as $item) {
            $arrow = self::topLevelArrow($item);
            $candidate = $arrow === null ? $item : substr($item, 0, $arrow);
            array_push($literals, ...self::literals($candidate));
        }

        return $literals;
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
        $depth = 0;
        $length = strlen($expression);

        for ($index = 0; $index < $length; $index++) {
            $character = $expression[$index];

            if ($character === '(') {
                $depth++;

                continue;
            }

            if ($character === ')') {
                $depth--;

                continue;
            }

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

            if ($depth === 0) {
                $literals[] = $quote === '\''
                    ? str_replace(['\\\\', "\\'"], ['\\', "'"], $literal)
                    : stripcslashes($literal);
            }
        }

        return $literals;
    }
}
