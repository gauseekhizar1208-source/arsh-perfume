<?php

namespace App\Support;

/**
 * All money maths in this application is done in integer paise to avoid floating point drift.
 * Database columns are DECIMAL(12,2); convert at the edges with toPaise()/fromPaise().
 */
final class Money
{
    public static function toPaise(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round(((float) $amount) * 100);
    }

    /** Integer paise to a DECIMAL-safe string, e.g. 10950 => "109.50". */
    public static function fromPaise(int $paise): string
    {
        $negative = $paise < 0;
        $paise = abs($paise);

        return ($negative ? '-' : '').intdiv($paise, 100).'.'.str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Indian digit grouping (12,34,567.00) with the rupee symbol. */
    public static function format(string|int|float|null $amount): string
    {
        $paise = self::toPaise($amount);
        $negative = $paise < 0;
        $paise = abs($paise);
        $rupees = (string) intdiv($paise, 100);
        $fraction = $paise % 100;

        if (strlen($rupees) > 3) {
            $last3 = substr($rupees, -3);
            $rest = substr($rupees, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $rupees = $rest.','.$last3;
        }

        $symbol = function_exists('setting') ? (string) setting('currency_symbol', '₹') : '₹';
        $text = $symbol.$rupees.($fraction ? '.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT) : '');

        return ($negative ? '-' : '').$text;
    }
}
