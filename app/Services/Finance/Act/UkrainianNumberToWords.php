<?php

namespace App\Services\Finance\Act;

class UkrainianNumberToWords
{
    /**
     * Convert float/decimal amount to Ukrainian words with currency.
     * Example: 50000.00 -> "П'ятдесят тисяч гривень 00 копійок, без ПДВ"
     */
    public static function convert(float|int|string $amount, bool $withVatNote = true): string
    {
        $amount = (float)$amount;
        $hryvnias = (int)floor(abs($amount));
        $kopiykas = (int)round((abs($amount) - $hryvnias) * 100);

        if ($kopiykas >= 100) {
            $hryvnias += 1;
            $kopiykas = 0;
        }

        $words = self::integerToWords($hryvnias);

        // Word for hryvnia
        $hryvniaWord = self::pluralForm($hryvnias, 'гривня', 'гривні', 'гривень');

        // Word for kopiyka
        $kopiykaWord = self::pluralForm($kopiykas, 'копійка', 'копійки', 'копійок');
        $kopiykasFormatted = sprintf('%02d %s', $kopiykas, $kopiykaWord);

        $result = trim("{$words} {$hryvniaWord} {$kopiykasFormatted}");

        // Capitalize first letter with multibyte support
        $firstChar = mb_strtoupper(mb_substr($result, 0, 1, 'UTF-8'), 'UTF-8');
        $rest = mb_substr($result, 1, null, 'UTF-8');
        $result = $firstChar . $rest;

        if ($withVatNote) {
            $result .= ', без ПДВ';
        }

        return $result;
    }

    /**
     * Format a date into Ukrainian words: e.g. "18 вересня 2026 р."
     */
    public static function formatUkrainianDate(\DateTimeInterface|string|null $date): string
    {
        if (!$date) {
            $date = \Carbon\Carbon::now();
        } elseif (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        $months = [
            1 => 'січня',
            2 => 'лютого',
            3 => 'березня',
            4 => 'квітня',
            5 => 'травня',
            6 => 'червня',
            7 => 'липня',
            8 => 'серпня',
            9 => 'вересня',
            10 => 'жовтня',
            11 => 'листопада',
            12 => 'грудня',
        ];

        $day = $date->format('j');
        $month = $months[(int)$date->format('n')] ?? '';
        $year = $date->format('Y');

        return "{$day} {$month} {$year} р.";
    }

    /**
     * Convert integer to words.
     */
    public static function integerToWords(int $number): string
    {
        if ($number === 0) {
            return 'нуль';
        }

        $units = [
            1 => ['одна', 'один'], // 0 - fem (thousand, hryvnia), 1 - masc (million, billion)
            2 => ['дві', 'два'],
            3 => 'три',
            4 => 'чотири',
            5 => "п'ять",
            6 => 'шість',
            7 => 'сім',
            8 => 'вісім',
            9 => "дев'ять",
        ];

        $teens = [
            10 => 'десять',
            11 => 'одинадцять',
            12 => 'дванадцять',
            13 => 'тринадцять',
            14 => 'чотирнадцять',
            15 => "п'ятнадцять",
            16 => 'шістнадцять',
            17 => 'сімнадцять',
            18 => 'вісімнадцять',
            19 => "дев'ятнадцять",
        ];

        $tens = [
            2 => 'двадцять',
            3 => 'тридцять',
            4 => 'сорок',
            5 => "п'ятдесят",
            6 => 'шістдесят',
            7 => 'сімдесят',
            8 => 'вісімдесят',
            9 => "дев'яносто",
        ];

        $hundreds = [
            1 => 'сто',
            2 => 'двісті',
            3 => 'триста',
            4 => 'чотириста',
            5 => "п'ятсот",
            6 => 'шістсот',
            7 => 'сімсот',
            8 => 'вісімсот',
            9 => "дев'ятсот",
        ];

        $triplets = [];
        $temp = $number;
        while ($temp > 0) {
            $triplets[] = $temp % 1000;
            $temp = (int)floor($temp / 1000);
        }

        $scales = [
            0 => ['feminine' => true, 'one' => '', 'few' => '', 'many' => ''],
            1 => ['feminine' => true, 'one' => 'тисяча', 'few' => 'тисячі', 'many' => 'тисяч'],
            2 => ['feminine' => false, 'one' => 'мільйон', 'few' => 'мільйони', 'many' => 'мільйонів'],
            3 => ['feminine' => false, 'one' => 'мільярд', 'few' => 'мільярди', 'many' => 'мільярдів'],
        ];

        $resultWords = [];

        for ($i = count($triplets) - 1; $i >= 0; $i--) {
            $triplet = $triplets[$i];
            if ($triplet === 0) {
                continue;
            }

            $h = (int)floor($triplet / 100);
            $t = (int)floor(($triplet % 100) / 10);
            $u = $triplet % 10;
            $isFeminine = $scales[$i]['feminine'];

            $tripletWords = [];

            if ($h > 0) {
                $tripletWords[] = $hundreds[$h];
            }

            if ($t === 1) {
                $tripletWords[] = $teens[$t * 10 + $u];
            } else {
                if ($t > 1) {
                    $tripletWords[] = $tens[$t];
                }
                if ($u > 0) {
                    if (is_array($units[$u])) {
                        $tripletWords[] = $isFeminine ? $units[$u][0] : $units[$u][1];
                    } else {
                        $tripletWords[] = $units[$u];
                    }
                }
            }

            if ($i > 0) {
                $tripletWords[] = self::pluralForm($triplet, $scales[$i]['one'], $scales[$i]['few'], $scales[$i]['many']);
            }

            $resultWords[] = implode(' ', $tripletWords);
        }

        return implode(' ', $resultWords);
    }

    /**
     * Choose correct Ukrainian plural form.
     */
    public static function pluralForm(int $count, string $one, string $few, string $many): string
    {
        $count = abs($count) % 100;
        $rem = $count % 10;

        if ($count > 10 && $count < 20) {
            return $many;
        }

        if ($rem > 1 && $rem < 5) {
            return $few;
        }

        if ($rem === 1) {
            return $one;
        }

        return $many;
    }
}
