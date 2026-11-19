<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small declarative validator.
 *
 *   $v = Validator::make($input, [
 *       'first_name' => ['required', 'max:60'],
 *       'age'        => ['required', 'int', 'between:16,65'],
 *       'sex'        => ['required', 'in:Male,Female'],
 *   ], ['first_name' => 'first name']);
 *
 * Supported rules: required, nullable, int, max:N, min:N (strings),
 * between:A,B (integers), in:a,b,c, regex:/.../
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param array<string, mixed>        $input
     * @param array<string, list<string>> $rules
     * @param array<string, string>       $labels
     */
    private function __construct(array $input, array $rules, array $labels)
    {
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $label = $labels[$field] ?? str_replace('_', ' ', $field);

            if ($value === null || $value === '') {
                if (in_array('required', $fieldRules, true)) {
                    $this->errors[$field] = ucfirst($label) . ' is required.';
                } else {
                    $this->data[$field] = null;
                }
                continue;
            }

            $error = $this->check($value, $fieldRules, $label);
            if ($error !== null) {
                $this->errors[$field] = $error;
                continue;
            }

            $this->data[$field] = in_array('int', $fieldRules, true) ? (int) $value : $value;
        }
    }

    /**
     * @param array<string, mixed>        $input
     * @param array<string, list<string>> $rules
     * @param array<string, string>       $labels
     */
    public static function make(array $input, array $rules, array $labels = []): self
    {
        return new self($input, $rules, $labels);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->data;
    }

    /** @param list<string> $rules */
    private function check(mixed $value, array $rules, string $label): ?string
    {
        $label = ucfirst($label);

        if (!is_scalar($value)) {
            return "{$label} is invalid.";
        }
        $value = (string) $value;

        foreach ($rules as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

            $error = match ($name) {
                'int' => preg_match('/^-?\d+$/', $value) ? null : "{$label} must be a whole number.",
                'max' => mb_strlen($value) <= (int) $arg ? null : "{$label} must be at most {$arg} characters.",
                'min' => mb_strlen($value) >= (int) $arg ? null : "{$label} must be at least {$arg} characters.",
                'between' => $this->between($value, (string) $arg, $label),
                'in' => in_array($value, explode(',', (string) $arg), true) ? null : "Please choose a valid {$label}.",
                'regex' => preg_match((string) $arg, $value) ? null : "{$label} format is invalid.",
                default => null,
            };

            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    private function between(string $value, string $arg, string $label): ?string
    {
        [$min, $max] = array_map('intval', explode(',', $arg));
        if (!preg_match('/^-?\d+$/', $value)) {
            return "{$label} must be a whole number.";
        }

        return ((int) $value >= $min && (int) $value <= $max) ? null : "{$label} must be between {$min} and {$max}.";
    }
}
