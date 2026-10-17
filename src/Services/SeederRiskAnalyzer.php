<?php

namespace Thetheago\SeederVersioning\Services;

/**
 * Heuristic static analysis of a seeder's source with token_get_all(): flags known calls that
 * delete data or make a seeder unsafe to run again. Nothing is executed; comments and strings
 * are ignored, so the result only depends on the code.
 */
class SeederRiskAnalyzer
{
    public const HIGH = 'high';
    public const MEDIUM = 'medium';

    protected const DELETES = ['truncate', 'delete', 'forcedelete'];
    protected const RAW_SQL = ['statement', 'unprepared'];
    protected const INSERTS = ['insert', 'create', 'insertgetid'];
    protected const IDEMPOTENT = ['upsert', 'updateorcreate', 'firstorcreate', 'updateorinsert', 'insertorignore'];
    protected const SEEDER_CALLS = ['call', 'callwith', 'callsilent', 'callonce'];
    protected const FOREIGN_KEYS = ['disableforeignkeyconstraints', 'withoutforeignkeyconstraints'];

    protected const NAME_TOKENS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE];
    protected const SKIPPED_TOKENS = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG];

    /**
     * @return array<int, array{level: string, message: string}>
     */
    public function analyze(string $code): array
    {
        $tokens = $this->tokens($code);
        $risks = [];
        $insert = null;
        $idempotent = false;

        foreach ($tokens as $i => [$type, $text]) {
            if (! in_array($type, self::NAME_TOKENS, true) || ($tokens[$i + 1][1] ?? null) !== '(') {
                continue;
            }

            $name = strtolower($text);
            $operator = $tokens[$i - 1][1] ?? null;
            $isMethod = in_array($operator, ['->', '?->', '::'], true);

            if (! $isMethod) {
                // Plain function call; `function factory(` or `new Factory(` are not calls.
                if (in_array($tokens[$i - 1][0] ?? null, [T_FUNCTION, T_NEW], true)) {
                    continue;
                }

                if ($name === 'factory') {
                    $risks[] = [self::MEDIUM, 'factory() creates random data'];
                }

                continue;
            }

            if (in_array($name, self::DELETES, true)) {
                $target = $this->target($tokens, $i - 1);
                $risks[] = [self::HIGH, "{$text}()" . ($target ? " on {$target}" : '')];
            } elseif (in_array($name, self::RAW_SQL, true) && $this->rawSqlDeletes($tokens[$i + 2] ?? null)) {
                $risks[] = [self::HIGH, "{$text}() with " . $this->rawSqlDeletes($tokens[$i + 2])];
            } elseif (in_array($name, self::INSERTS, true)) {
                $insert ??= $text;
            } elseif (in_array($name, self::IDEMPOTENT, true)) {
                $idempotent = true;
            } elseif (in_array($name, self::SEEDER_CALLS, true) && ($tokens[$i - 2][1] ?? null) === '$this') {
                $risks[] = [self::MEDIUM, "\$this->{$text}() runs other seeders"];
            } elseif ($name === 'factory' && $operator === '::') {
                $risks[] = [self::MEDIUM, 'factory() creates random data'];
            } elseif (in_array($name, self::FOREIGN_KEYS, true)) {
                $risks[] = [self::MEDIUM, "{$text}() disables foreign keys"];
            }
        }

        if ($insert && ! $idempotent) {
            $risks[] = [self::MEDIUM, "{$insert}() without upsert"];
        }

        $unique = [];

        foreach ($risks as [$level, $message]) {
            $unique[$level . $message] = ['level' => $level, 'message' => $message];
        }

        // High risks first, then in the order they appear in the file.
        usort($unique, fn (array $a, array $b) => ($a['level'] === self::HIGH ? 0 : 1) <=> ($b['level'] === self::HIGH ? 0 : 1));

        return array_values($unique);
    }

    /**
     * @return array<int, array{0: int|null, 1: string}>
     */
    protected function tokens(string $code): array
    {
        $tokens = [];

        foreach (token_get_all($code) as $token) {
            if (is_array($token) && in_array($token[0], self::SKIPPED_TOKENS, true)) {
                continue;
            }

            $tokens[] = is_array($token) ? [$token[0], $token[1]] : [null, $token];
        }

        return $tokens;
    }

    /**
     * What a call acts on, read from the start of its statement: `DB::table('users')->truncate()`
     * gives "table users", `User::query()->delete()` gives "User".
     *
     * @param array<int, array{0: int|null, 1: string}> $tokens
     */
    protected function target(array $tokens, int $operator): ?string
    {
        $start = $operator;
        $depth = 0;

        while ($start > 0) {
            $previous = $tokens[$start - 1][1];

            if ($previous === ')') {
                $depth++;
            } elseif ($previous === '(' && $depth > 0) {
                $depth--;
            } elseif ($depth === 0 && in_array($previous, [';', '{', '}', '=', '(', ','], true)) {
                break;
            }

            $start--;
        }

        for ($i = $start; $i < $operator; $i++) {
            if (strtolower($tokens[$i][1]) === 'table'
                && ($tokens[$i + 1][1] ?? null) === '('
                && ($tokens[$i + 2][0] ?? null) === T_CONSTANT_ENCAPSED_STRING) {
                return 'table ' . trim($tokens[$i + 2][1], '\'"');
            }
        }

        if (in_array($tokens[$start][0], self::NAME_TOKENS, true) && ($tokens[$start + 1][1] ?? null) === '::') {
            return class_basename($tokens[$start][1]);
        }

        return null;
    }

    /**
     * The destructive SQL keyword a raw statement starts with (DROP, TRUNCATE, DELETE), if any.
     *
     * @param array{0: int|null, 1: string}|null $argument
     */
    protected function rawSqlDeletes(?array $argument): ?string
    {
        if (($argument[0] ?? null) !== T_CONSTANT_ENCAPSED_STRING) {
            return null;
        }

        return preg_match('/^\s*(DROP|TRUNCATE|DELETE)\b/i', trim($argument[1], '\'"'), $matches)
            ? strtoupper($matches[1])
            : null;
    }
}
