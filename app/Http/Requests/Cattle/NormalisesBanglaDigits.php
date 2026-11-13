<?php

namespace App\Http\Requests\Cattle;

/** Lets a farmer type ২০২৪-০৩-১৫ or ৩: Bangla digits in the listed fields become 0–9 before validation. */
trait NormalisesBanglaDigits
{
    /** @param  list<string>  $keys */
    protected function normaliseDigits(array $keys): void
    {
        $toAscii = array_combine(['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'], str_split('0123456789'));
        $normalised = [];
        foreach ($keys as $key) {
            if (is_string($this->input($key))) {
                $normalised[$key] = trim(strtr($this->input($key), $toAscii));
            }
        }
        $this->merge($normalised);
    }
}
