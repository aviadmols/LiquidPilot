<?php

namespace App\Domain\Agent;

/**
 * Parses JSON with error handling; optionally validates required keys or allowed section handles.
 * On failure can invoke a JSON fix callback (e.g. AI) up to maxRetries, then re-validate.
 *
 * Inputs: Raw string, optional validation (required keys, allowed handles), optional fixer callable.
 * Outputs: Decoded array or throws.
 * Side effects: May call fixer (e.g. AI request).
 */
class JsonGuard
{
    public const MAX_RETRIES = 2;

    /**
     * @param array{required_keys?: list<string>, allowed_section_handles?: list<string>} $constraints
     * @param callable(string): string|null $fixer Optional; receives invalid JSON string, returns fixed string or null.
     * @return array<string, mixed>
     */
    public function parseAndValidate(string $raw, array $constraints = [], ?callable $fixer = null): array
    {
        $lastError = null;
        $current = $raw;
        $maxTries = $fixer ? (1 + self::MAX_RETRIES) : 1;

        for ($i = 0; $i < $maxTries; $i++) {
            $decoded = $this->decode($current);
            if ($decoded === null) {
                $lastError = 'Invalid JSON';
                if ($fixer && $i < $maxTries - 1) {
                    $fixed = $fixer($current);
                    if ($fixed !== null && $fixed !== '') {
                        $current = $fixed;
                    }
                }
                continue;
            }
            $validationError = $this->validate($decoded, $constraints);
            if ($validationError === null) {
                return $decoded;
            }
            $lastError = $validationError;
            if ($fixer && $i < $maxTries - 1) {
                $fixed = $fixer($current);
                if ($fixed !== null && $fixed !== '') {
                    $current = $fixed;
                }
            }
        }

        throw new \InvalidArgumentException('JSON validation failed: ' . ($lastError ?? 'invalid'));
    }

    public function decode(string $raw): ?array
    {
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }
        return $decoded;
    }

    /**
     * @param array<string, mixed> $data
     * @param array{required_keys?: list<string>, allowed_section_handles?: list<string>} $constraints
     */
    public function validate(array $data, array $constraints): ?string
    {
        if (isset($constraints['required_keys'])) {
            foreach ($constraints['required_keys'] as $key) {
                if (!array_key_exists($key, $data)) {
                    return "Missing required key: {$key}";
                }
            }
        }
        if (isset($constraints['allowed_section_handles']) && !empty($constraints['allowed_section_handles'])) {
            $allowed = array_flip($constraints['allowed_section_handles']);
            $sections = $data['sections'] ?? $data['section_order'] ?? [];
            if (is_array($sections)) {
                $handles = is_array(reset($sections))
                    ? array_keys($sections)
                    : $sections;
                foreach ($handles as $handle) {
                    $h = is_string($handle) ? $handle : ($handle['type'] ?? $handle['handle'] ?? null);
                    if ($h !== null && !isset($allowed[$h])) {
                        return "Section handle not in catalog: {$h}";
                    }
                }
            }
        }
        return null;
    }
}
