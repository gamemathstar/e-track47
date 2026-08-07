<?php

namespace App\Services;

class BulkUploadLabelMatcher
{
    public static function normalizeKey(string $value): string
    {
        $value = preg_replace('/^commitment\s+\d+\s*:\s*/i', '', trim($value)) ?? trim($value);

        return preg_replace('/\s+/', ' ', strtolower($value)) ?? '';
    }

    public static function labelsAreEquivalent(string $left, string $right): bool
    {
        $leftKey = self::normalizeKey($left);
        $rightKey = self::normalizeKey($right);

        if ($leftKey === '' || $rightKey === '') {
            return false;
        }

        if ($leftKey === $rightKey) {
            return true;
        }

        if (str_contains($leftKey, $rightKey) || str_contains($rightKey, $leftKey)) {
            return true;
        }

        similar_text($leftKey, $rightKey, $percent);

        return $percent >= 88;
    }
}
