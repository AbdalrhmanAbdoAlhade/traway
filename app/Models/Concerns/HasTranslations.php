<?php

namespace App\Models\Concerns;

trait HasTranslations
{
    /**
     * Read a JSON {"ar": "...", "en": "..."} attribute in the given (or current) locale.
     */
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        $value = $this->getAttribute($attribute);

        if (! is_array($value)) {
            return $value;
        }

        $locale ??= app()->getLocale();

        return $value[$locale]
            ?? $value[config('app.fallback_locale')]
            ?? (reset($value) ?: null);
    }
}
