<?php

namespace Mary\Support;

use RuntimeException;

class DynamicClassCandidateResolver
{
    private const INTERPOLATION_PATTERN = '/\{\$(?:this->)?(?<callable>[A-Za-z_][A-Za-z0-9_]*)\([^{}]*\)\}|\{\$(?:this->)?(?<braced>[A-Za-z_][A-Za-z0-9_]*)\}|\$(?:this->(?<property>[A-Za-z_][A-Za-z0-9_]*)|(?<variable>[A-Za-z_][A-Za-z0-9_]*))/';

    /** @return array<int, string> */
    public static function expand(string $literal, string $source, string $file): array
    {
        return self::expandCandidate($literal, $source, $file);
    }

    /**
     * Expand one candidate while keeping dependency ancestry scoped to the
     * replacement value. Independent references in the same class string do
     * not inherit each other's ancestry.
     *
     * @param  array<int, string>  $references
     * @return array<int, string>
     */
    private static function expandCandidate(string $candidate, string $source, string $file, array $references = []): array
    {
        if (! preg_match(self::INTERPOLATION_PATTERN, $candidate, $match)) {
            return [$candidate];
        }

        $name = $match['callable'] ?: ($match['braced'] ?: ($match['property'] ?: $match['variable']));
        $reference = ($match['callable'] ? 'method:' : 'variable:').$name;

        if (in_array($reference, $references, true)) {
            throw new RuntimeException("Cyclic dynamic Mary class reference [{$match[0]}] in [{$file}].");
        }

        $values = $match['callable']
            ? self::methodReturnValues($source, $name)
            : self::variableValues($source, $name);

        if ($values === []) {
            throw new RuntimeException("Unable to resolve dynamic Mary class [{$match[0]}] in [{$file}].");
        }

        $resolved = [];

        foreach ($values as $value) {
            foreach (self::expandCandidate($value, $source, $file, [...$references, $reference]) as $expandedValue) {
                $next = preg_replace_callback(
                    '/'.preg_quote($match[0], '/').'/',
                    fn (): string => $expandedValue,
                    $candidate,
                    1
                ) ?? $candidate;
                array_push($resolved, ...self::expandCandidate($next, $source, $file, $references));
            }
        }

        return array_values(array_unique($resolved));
    }

    /** @return array<int, string> */
    private static function variableValues(string $source, string $name, array $seen = []): array
    {
        $key = 'variable:'.$name;

        if (in_array($key, $seen, true)) {
            return [];
        }

        $seen[] = $key;

        preg_match_all(
            '/(?:\$this->'.preg_quote($name, '/').'|\$'.preg_quote($name, '/').')\s*=(?!=|>)/',
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $values = [];

        foreach ($matches[0] as [$assignment, $offset]) {
            $expression = self::assignedExpression($source, $offset + strlen($assignment));
            array_push($values, ...self::quotedLiterals($expression));
            array_push($values, ...self::referencedValues($expression, $source, $seen));
        }

        return self::unique($values);
    }

    /** @return array<int, string> */
    private static function methodReturnValues(string $source, string $name, array $seen = []): array
    {
        $key = 'method:'.$name;

        if (in_array($key, $seen, true)) {
            return [];
        }

        $seen[] = $key;

        if (! preg_match('/function\s+'.preg_quote($name, '/').'\s*\(/', $source, $match, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $method = $match[0][1];
        $open = strpos($source, '{', $method + strlen($match[0][0]));

        if ($open === false || ($body = self::braced($source, $open)) === null) {
            return [];
        }

        // Keep a conservative superset: any literal in a referenced method may
        // participate in its returned class expression.
        $values = self::quotedLiterals($body);

        array_push($values, ...self::referencedValues($body, $source, $seen));

        return self::unique($values);
    }

    /** @return array<int, string> */
    private static function referencedValues(string $expression, string $source, array $seen): array
    {
        $values = [];

        preg_match_all(
            '/\$this->(?<method>[A-Za-z_][A-Za-z0-9_]*)\s*\(/',
            $expression,
            $methods
        );

        foreach ($methods['method'] as $method) {
            array_push($values, ...self::methodReturnValues($source, $method, $seen));
        }

        preg_match_all(
            '/\$this->(?<property>[A-Za-z_][A-Za-z0-9_]*)\b(?!\s*\()/',
            $expression,
            $properties
        );

        foreach ($properties['property'] as $property) {
            array_push($values, ...self::variableValues($source, $property, $seen));
        }

        preg_match_all(
            '/(?<!->)\$(?<variable>[A-Za-z_][A-Za-z0-9_]*)\b(?!\s*->)(?!\s*\()/',
            $expression,
            $variables
        );

        foreach ($variables['variable'] as $variable) {
            if ($variable !== 'this') {
                array_push($values, ...self::variableValues($source, $variable, $seen));
            }
        }

        return self::unique($values);
    }

    private static function assignedExpression(string $source, int $start): string
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

            if (($character === ';' || $character === ',' || $character === ')')
                && $round === 0
                && $square === 0
                && $curly === 0) {
                return substr($source, $start, $index - $start);
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
