<?php

namespace App\Http\Requests;

trait NormalizesDecimalInput
{
    /** @param array<int, string> $fields */
    protected function normalizeDecimalInput(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (is_string($value) && str_contains($value, ',')) {
                $normalized[$field] = str_replace(['.', ','], ['', '.'], trim($value));
            }
        }

        $this->merge($normalized);
    }
}
