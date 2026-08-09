<?php

namespace Mary\Support;

use RuntimeException;

class DynamicClassCandidateResolver
{
    private const INTERPOLATION_PATTERN = '/\{\$(?:this->)?(?<callable>[A-Za-z_][A-Za-z0-9_]*)\([^{}]*\)\}|\{\$(?<braced>[A-Za-z_][A-Za-z0-9_]*)\}|\$(?<variable>[A-Za-z_][A-Za-z0-9_]*)/';

    /** @return array<int, string> */
    public static function expand(string $literal, string $source, string $file): array
    {
        $expanded = [$literal];

        while (true) {
            $resolved = [];
            $found = false;

            foreach ($expanded as $candidate) {
                if (! preg_match(self::INTERPOLATION_PATTERN, $candidate, $match)) {
                    $resolved[] = $candidate;

                    continue;
                }

                $found = true;
                $name = $match['callable'] ?: ($match['braced'] ?: $match['variable']);
                $values = $match['callable']
                    ? self::methodReturnValues($source, $name)
                    : self::variableValues($source, $name);

                if ($values === []) {
                    throw new RuntimeException("Unable to resolve dynamic Mary class [{$match[0]}] in [{$file}].");
                }

                foreach ($values as $value) {
                    $resolved[] = str_replace($match[0], $value, $candidate);
                }
            }

            $expanded = array_values(array_unique($resolved));

            if (! $found) {
                return $expanded;
            }
        }
    }

    /** @return array<int, string> */
    private static function variableValues(string $source, string $name): array
    {
        preg_match_all(
            '/(?:\$this->'.preg_quote($name, '/').'|\$'.preg_quote($name, '/').')\s*=(?!=|>)/',
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $values = [];

        foreach ($matches[0] as [$assignment, $offset]) {
            $expression = self::terminated($source, $offset + strlen($assignment));
            array_push($values, ...self::quotedLiterals($expression));
        }

        return self::unique($values);
    }

    /** @return array<int, string> */
    private static function methodReturnValues(string $source, string $name): array
    {
        if (! preg_match('/function\s+'.preg_quote($name, '/').'\s*\(/', $source, $match, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $method = $match[0][1];
        $open = strpos($source, '{', $method + strlen($match[0][0]));

        if ($open === false || ($body = self::braced($source, $open)) === null) {
            return [];
        }

        preg_match_all(
            '/(?:return|=>)\s*(?:\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\])*)")/s',
            $body,
            $returns,
            PREG_SET_ORDER
        );

        $values = [];

        foreach ($returns as $return) {
            $singleQuoted = $return[1] ?? '';
            $values[] = $singleQuoted !== ''
                ? str_replace(['\\\\', "\\'"], ['\\', "'"], $singleQuoted)
                : stripcslashes($return[2]);
        }

        return self::unique($values);
    }

    private static function terminated(string $source, int $start): string
    {
        $round = 0;
        $square = 0;
        $curly = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = $start; $index < $length; $index++) {
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

            if ($character === ';' && $round === 0 && $square === 0 && $curly === 0) {
                return substr($source, $start, $index - $start);
            }
        }

        return substr($source, $start);
    }

    private static function braced(string $source, int $open): ?string
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
                return substr($source, $open + 1, $index - $open - 1);
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private static function quotedLiterals(string $source): array
    {
        preg_match_all(
            '/(?:\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\])*)")/s',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        $literals = [];

        foreach ($matches as $match) {
            $singleQuoted = $match[1] ?? '';
            $literals[] = $singleQuoted !== ''
                ? str_replace(['\\\\', "\\'"], ['\\', "'"], $singleQuoted)
                : stripcslashes($match[2]);
        }

        return $literals;
    }

    /** @param  array<int, string>  $values */
    private static function unique(array $values): array
    {
        $values = array_values(array_unique(array_filter($values, fn (string $value): bool => $value !== '')));
        sort($values);

        return $values;
    }
}
