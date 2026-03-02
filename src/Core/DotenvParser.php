<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Core;

use KaririCode\Dotenv\Exception\ParseException;

/**
 * Parses .env file content into key-value string pairs.
 *
 * Supported syntax (superset of POSIX shell variable assignment):
 *
 *   - Empty lines and lines starting with # are ignored.
 *   - `export` prefix is silently stripped.
 *   - Unquoted, single-quoted, and double-quoted values.
 *   - Double-quoted values support escape sequences: \n, \r, \t, \\, \", \$.
 *   - Single-quoted values are literal (no escapes, no interpolation).
 *   - Variable interpolation in unquoted and double-quoted values:
 *     ${VAR}, $VAR — resolved against already-parsed variables, then $_ENV, then $_SERVER.
 *   - Inline comments: `VALUE # comment` (only outside quotes).
 *   - Multiline double-quoted values (embedded newlines).
 *
 * Reference: Docker .env syntax, Bash variable assignment, 12-Factor App methodology.
 *
 * ARFA 1.3 P1: The parser produces immutable string pairs — no mutation after parse.
 * ARFA 1.3 P4: Protocol-agnostic — works with any input stream providing raw .env content.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final class DotenvParser
{
    private const string VARIABLE_NAME_PATTERN = '/\A[A-Za-z_][A-Za-z0-9_.]*\z/';
    private const string STRICT_NAME_PATTERN = '/\A[A-Z][A-Z0-9_]*\z/';

    /**
     * Parses raw .env content into an ordered map of name → raw value.
     *
     * @return array<string, string> Variable name → raw string value (after quote stripping and interpolation).
     *
     * @throws ParseException On syntax errors (invalid name, unterminated quote, circular reference).
     */
    public function parse(string $content, string $filePath = '.env', bool $strictNames = false): array
    {
        /** @var array<string, string> $variables */
        $variables = [];
        $lines = $this->normalizeLines($content);
        $lineNumber = 0;

        while ($lineNumber < count($lines)) {
            $line = $lines[$lineNumber];
            // Increment first: $lineNumber becomes 1-indexed (for error messages) and
            // simultaneously equals the 0-indexed position of the next line (for multiline
            // continuation in parseDoubleQuoted). This dual purpose is intentional.
            ++$lineNumber;

            $trimmed = trim($line);

            // Skip empty lines and comments
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Strip optional 'export' prefix
            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = ltrim(substr($trimmed, 7));
            }

            // Find the = separator
            $equalsPos = strpos($trimmed, '=');
            if ($equalsPos === false) {
                // Bare variable name without value — treat as empty string
                $name = $trimmed;
                $rawValue = '';
            } else {
                $name = rtrim(substr($trimmed, 0, $equalsPos));
                $rawValue = ltrim(substr($trimmed, $equalsPos + 1));
            }

            // Validate variable name
            $namePattern = $strictNames ? self::STRICT_NAME_PATTERN : self::VARIABLE_NAME_PATTERN;
            if (preg_match($namePattern, $name) !== 1) {
                throw ParseException::invalidVariableName($name, $lineNumber, $filePath, $strictNames);
            }

            // Parse value (handles quoting and multiline)
            [$parsedValue, $consumed] = $this->parseValue($rawValue, $lines, $lineNumber, $filePath, $variables);
            $lineNumber += $consumed;

            $variables[$name] = $parsedValue;
        }

        return $variables;
    }

    // ── Value Parsing ─────────────────────────────────────────────────

    /**
     * Parses the value portion of a KEY=VALUE line.
     *
     * @param array<string, string> $resolvedVariables Already-parsed variables for interpolation.
     *
     * @return array{0: string, 1: int} Tuple of [parsed value, additional lines consumed].
     */
    private function parseValue(
        string $rawValue,
        array $lines,
        int $currentLine,
        string $filePath,
        array $resolvedVariables,
    ): array {
        if ($rawValue === '') {
            return ['', 0];
        }

        $firstChar = $rawValue[0];

        // Double-quoted value — supports escapes, interpolation, multiline
        if ($firstChar === '"') {
            return $this->parseDoubleQuoted($rawValue, $lines, $currentLine, $filePath, $resolvedVariables);
        }

        // Single-quoted value — literal, no escapes, no interpolation
        if ($firstChar === "'") {
            return $this->parseSingleQuoted($rawValue, $currentLine, $filePath);
        }

        // Unquoted value — strip inline comments, apply interpolation
        return [$this->parseUnquoted($rawValue, $resolvedVariables), 0];
    }

    private function parseDoubleQuoted(
        string $rawValue,
        array $lines,
        int $currentLine,
        string $filePath,
        array $resolvedVariables,
    ): array {
        // Remove opening quote
        $value = substr($rawValue, 1);
        $result = '';
        $extraLines = 0;

        while (true) {
            $length = strlen($value);

            for ($i = 0; $i < $length; ++$i) {
                $char = $value[$i];

                // Escape sequence
                if ($char === '\\' && $i + 1 < $length) {
                    $next = $value[$i + 1];
                    $result .= match ($next) {
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        '"' => '"',
                        '\\' => '\\',
                        '$' => '$',
                        default => '\\' . $next,
                    };
                    ++$i;

                    continue;
                }

                // Closing quote found
                if ($char === '"') {
                    $result = $this->expandVariables($result, $resolvedVariables);

                    return [$result, $extraLines];
                }

                $result .= $char;
            }

            // Value continues on next line
            $nextLineIndex = $currentLine + $extraLines;
            if ($nextLineIndex >= count($lines)) {
                throw ParseException::unterminatedQuote($currentLine, $filePath);
            }

            $result .= "\n";
            $value = $lines[$nextLineIndex];
            ++$extraLines;
        }
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parseSingleQuoted(string $rawValue, int $currentLine, string $filePath): array
    {
        $closingPos = strpos($rawValue, "'", 1);

        if ($closingPos === false) {
            throw ParseException::unterminatedQuote($currentLine, $filePath);
        }

        return [substr($rawValue, 1, $closingPos - 1), 0];
    }

    private function parseUnquoted(string $rawValue, array $resolvedVariables): string
    {
        // Strip inline comment: look for # preceded by whitespace, outside any quoting
        $value = preg_replace('/\s+#.*$/', '', $rawValue) ?? $rawValue;
        $value = trim($value);

        return $this->expandVariables($value, $resolvedVariables);
    }

    // ── Variable Expansion ────────────────────────────────────────────

    /**
     * Expands ${VAR} and $VAR references against resolved variables and the environment.
     *
     * Resolution order: parsed variables → $_ENV → $_SERVER → empty string.
     *
     * @param array<string, string> $resolvedVariables
     */
    private function expandVariables(string $value, array $resolvedVariables): string
    {
        // Expand ${VAR:-default} and ${VAR:+alternate} syntax
        $value = preg_replace_callback(
            '/\$\{([A-Za-z_][A-Za-z0-9_]*)(?:(:[-+])(.*?))?\}/',
            static function (array $matches) use ($resolvedVariables): string {
                $name = $matches[1];
                $operator = $matches[2] ?? '';
                $operand = $matches[3] ?? '';

                $resolved = $resolvedVariables[$name]
                    ?? $_ENV[$name]
                    ?? $_SERVER[$name]
                    ?? null;

                return match ($operator) {
                    // ${VAR:+alternate} — use alternate if VAR is set and non-empty
                    ':+' => ($resolved !== null && $resolved !== '') ? $operand : '',
                    // ${VAR:-default} — use default if VAR is unset or empty
                    ':-' => ($resolved !== null && $resolved !== '') ? $resolved : $operand,
                    // ${VAR} — plain substitution
                    default => $resolved ?? '',
                };
            },
            $value,
        ) ?? $value;

        // Expand bare $VAR syntax
        $value = preg_replace_callback(
            '/\$([A-Za-z_][A-Za-z0-9_]*)/',
            static function (array $matches) use ($resolvedVariables): string {
                $name = $matches[1];

                return $resolvedVariables[$name]
                    ?? $_ENV[$name]
                    ?? $_SERVER[$name]
                    ?? '';
            },
            $value,
        ) ?? $value;

        return $value;
    }

    // ── Line Normalization ────────────────────────────────────────────

    /**
     * Splits content into lines, normalizing line endings (CRLF → LF).
     *
     * @return list<string>
     */
    private function normalizeLines(string $content): array
    {
        $normalized = str_replace("\r\n", "\n", $content);
        $normalized = str_replace("\r", "\n", $normalized);

        return explode("\n", $normalized);
    }
}
