<?php

}
    }
        return $matches[0] ?? [];

        preg_match_all('/(?:[^\s"\']+|"[^"]*"|\'[^\']*\')+/', $query, $matches);
        // Split by spaces but preserve quoted strings
    {
    protected function tokenize(string $query): array
     */
     * Tokenize query string.
    /**

    }
        return $filters;

        }
            }
                }
                    $filters['context'][$field] = $value;
                    // Context field
                } else {
                    $filters['message_contains'][] = $value;
                } elseif ($field === 'message') {
                    $filters['level'][] = $value;
                } elseif ($field === 'level') {
                    $negate = false;
                    $filters['not'][$field] = $value;
                if ($negate) {

                $value = trim($matches[2], '"\'');
                $field = $matches[1];
            if (preg_match('/^(\w+):([\w"\']+)$/', $token, $matches)) {
            // Parse field:value

            }
                continue;
                }
                    $operator = $token;
                } else {
                    $negate = true;
                if ($token === 'NOT') {
            if (in_array($token, ['AND', 'OR', 'NOT'])) {
        foreach ($tokens as $token) {

        $negate = false;
        $operator = 'AND';

        $tokens = $this->tokenize($query);
        // Simple tokenizer for MVP

        ];
            'not' => [],
            'message_contains' => [],
            'context' => [],
            'level' => [],
        $filters = [
    {
    public function parse(string $query): array
     */
     *          NOT level:debug
     *          message:"connection failed"
     *          level:error OR level:critical
     * Supports: level:error AND user_id:123
     *
     * Parse structured query into filters.
    /**
{
class StructuredQueryParser

namespace Willypelz\LogPlatform\Services;

