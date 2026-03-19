<?php

namespace Willypelz\LogPlatform\Services;

class StructuredQueryParser
{
    /**
     * Parse structured query into filters.
     *
     * Supports: level:error AND user_id:123
     *          level:error OR level:critical
     *          message:"connection failed"
     *          NOT level:debug
     */
    public function parse(string $query): array
    {
        $filters = [
            'level' => [],
            'context' => [],
            'message_contains' => [],
            'not' => [],
        ];

        // Simple tokenizer for MVP
        $tokens = $this->tokenize($query);

        $operator = 'AND';
        $negate = false;

        foreach ($tokens as $token) {
            if (in_array($token, ['AND', 'OR', 'NOT'])) {
                if ($token === 'NOT') {
                    $negate = true;
                } else {
                    $operator = $token;
                }
                continue;
            }

            // Parse field:value
            if (preg_match('/^(\w+):([\w"\']+)$/', $token, $matches)) {
                $field = $matches[1];
                $value = trim($matches[2], '"\'');

                if ($negate) {
                    $filters['not'][$field] = $value;
                    $negate = false;
                } elseif ($field === 'level') {
                    $filters['level'][] = $value;
                } elseif ($field === 'message') {
                    $filters['message_contains'][] = $value;
                } else {
                    // Context field
                    $filters['context'][$field] = $value;
                }
            }
        }

        return $filters;
    }

    /**
     * Tokenize query string.
     */
    protected function tokenize(string $query): array
    {
        // Split by spaces but preserve quoted strings
        preg_match_all('/(?:[^\s"\']+|"[^"]*"|\'[^\']*\')+/', $query, $matches);

        return $matches[0] ?? [];
    }
}
