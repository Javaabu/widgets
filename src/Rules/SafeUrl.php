<?php

namespace Javaabu\Widgets\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Javaabu\Widgets\Support\Url;

/**
 * An http(s), mailto or tel URL, a path starting with / or a #fragment
 */
class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! Url::isSafe($value)) {
            $fail(__('The :attribute must be a link starting with https://, / or #.'));
        }
    }
}
