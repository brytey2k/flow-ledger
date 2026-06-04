<?php

declare(strict_types=1);

namespace App\Support;

class PhoneNumberFormatter
{
    /** @var array<string, string> */
    private const DIAL_CODES = [
        'GH' => '+233',
        'NG' => '+234',
        'KE' => '+254',
        'ZA' => '+27',
        'CI' => '+225',
        'GN' => '+224',
        'SN' => '+221',
        'CM' => '+237',
        'TZ' => '+255',
        'UG' => '+256',
        'ET' => '+251',
        'EG' => '+20',
        'US' => '+1',
        'CA' => '+1',
        'GB' => '+44',
        'DE' => '+49',
        'FR' => '+33',
        'IT' => '+39',
        'NL' => '+31',
    ];

    /** @return array<string, string> */
    public static function dialCodeMap(): array
    {
        return self::DIAL_CODES;
    }

    public static function assemble(string|null $countryCode, string|null $localNumber): string|null
    {
        $country = mb_strtoupper(trim((string) $countryCode));
        $number = trim((string) $localNumber);

        if ($number === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            return null;
        }

        $strippedDigits = ltrim($digits, '0');
        if ($strippedDigits !== '') {
            $digits = $strippedDigits;
        }

        $dialCode = self::DIAL_CODES[$country] ?? null;

        if ($dialCode === null) {
            return $digits;
        }

        return $dialCode . $digits;
    }

    /**
     * @param string|null $phone
     *
     * @return array{0: string, 1: string}
     */
    public static function split(string|null $phone): array
    {
        $raw = trim((string) $phone);

        if ($raw === '') {
            return ['GH', ''];
        }

        $normalized = preg_replace('/[^\d+]/', '', $raw) ?? $raw;
        $numberPart = preg_replace('/\D+/', '', $raw) ?? '';

        if ($normalized !== '' && str_starts_with($normalized, '+')) {
            $dialCodes = self::DIAL_CODES;

            uasort($dialCodes, static fn(string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

            foreach ($dialCodes as $country => $dialCode) {
                if (str_starts_with($normalized, $dialCode)) {
                    $local = substr($normalized, mb_strlen($dialCode));
                    $localDigits = preg_replace('/\D+/', '', $local) ?? '';

                    return [$country, $localDigits];
                }
            }
        }

        return ['GH', $numberPart];
    }
}
