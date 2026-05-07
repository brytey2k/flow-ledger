<?php

declare(strict_types=1);

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Get the currency symbol for a given currency code.
     *
     * @param string $currencyCode The 3-letter currency code (e.g., USD, GHS, EUR)
     */
    public static function getSymbol(string $currencyCode): string
    {
        $symbols = [
            'GHS' => 'GH₵',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'NZD' => 'NZ$',
            'INR' => '₹',
            'ZAR' => 'R',
            'NGN' => '₦',
            'KES' => 'KSh',
            'UGX' => 'USh',
            'TZS' => 'TSh',
            'XOF' => 'CFA',
            'XAF' => 'FCFA',
        ];

        return $symbols[strtoupper($currencyCode)] ?? strtoupper($currencyCode);
    }

    /**
     * Format an amount with its currency symbol.
     *
     * @param float $amount The amount to format
     * @param string $currencyCode The 3-letter currency code
     * @param int $decimals Number of decimal places (default: 2)
     */
    public static function format(float $amount, string $currencyCode, int $decimals = 2): string
    {
        $symbol = self::getSymbol($currencyCode);
        $formattedAmount = number_format($amount, $decimals);

        return $symbol . ' ' . $formattedAmount;
    }

    /**
     * Check if a transaction was converted from a different currency.
     *
     * @param string $currency The original transaction currency
     * @param string $reportingCurrency The reporting currency
     */
    public static function wasConverted(string $currency, string $reportingCurrency): bool
    {
        return strtoupper($currency) !== strtoupper($reportingCurrency);
    }
}
