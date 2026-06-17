<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\PhoneNumberFormatter;
use PHPUnit\Framework\TestCase;

class PhoneNumberFormatterTest extends TestCase
{
    // ── dialCodeMap ───────────────────────────────────────────────────────────

    public function test_dial_code_map_returns_array_of_country_codes(): void
    {
        $map = PhoneNumberFormatter::dialCodeMap();

        $this->assertIsArray($map);
        $this->assertNotEmpty($map);
    }

    public function test_dial_code_map_contains_ghana(): void
    {
        $map = PhoneNumberFormatter::dialCodeMap();

        $this->assertArrayHasKey('GH', $map);
        $this->assertSame('+233', $map['GH']);
    }

    public function test_dial_code_map_contains_nigeria(): void
    {
        $map = PhoneNumberFormatter::dialCodeMap();

        $this->assertArrayHasKey('NG', $map);
        $this->assertSame('+234', $map['NG']);
    }

    public function test_dial_code_map_contains_us_and_canada_with_same_code(): void
    {
        $map = PhoneNumberFormatter::dialCodeMap();

        $this->assertArrayHasKey('US', $map);
        $this->assertArrayHasKey('CA', $map);
        $this->assertSame('+1', $map['US']);
        $this->assertSame('+1', $map['CA']);
    }

    public function test_dial_code_map_contains_19_countries(): void
    {
        $map = PhoneNumberFormatter::dialCodeMap();

        $this->assertCount(19, $map);
    }

    // ── assemble ──────────────────────────────────────────────────────────────

    public function test_assemble_returns_null_for_empty_local_number(): void
    {
        $result = PhoneNumberFormatter::assemble('GH', '');

        $this->assertNull($result);
    }

    public function test_assemble_returns_null_for_null_local_number(): void
    {
        $result = PhoneNumberFormatter::assemble('GH', null);

        $this->assertNull($result);
    }

    public function test_assemble_returns_null_for_whitespace_only_local_number(): void
    {
        $result = PhoneNumberFormatter::assemble('GH', '   ');

        $this->assertNull($result);
    }

    public function test_assemble_returns_null_for_non_digit_only_local_number(): void
    {
        $result = PhoneNumberFormatter::assemble('GH', '---');

        $this->assertNull($result);
    }

    public function test_assemble_prepends_ghana_dial_code(): void
    {
        $result = PhoneNumberFormatter::assemble('GH', '244000000');

        $this->assertSame('+233244000000', $result);
    }

    public function test_assemble_prepends_nigeria_dial_code(): void
    {
        $result = PhoneNumberFormatter::assemble('NG', '8012345678');

        $this->assertSame('+2348012345678', $result);
    }

    public function test_assemble_strips_leading_zeros_from_local_number(): void
    {
        $result = PhoneNumberFormatter::assemble('GH', '0244000000');

        $this->assertSame('+233244000000', $result);
    }

    public function test_assemble_strips_non_digit_characters_from_local_number(): void
    {
        $result = PhoneNumberFormatter::assemble('GH', '024-400-0000');

        $this->assertSame('+233244000000', $result);
    }

    public function test_assemble_returns_raw_digits_for_unknown_country_code(): void
    {
        $result = PhoneNumberFormatter::assemble('XX', '123456789');

        $this->assertSame('123456789', $result);
    }

    public function test_assemble_is_case_insensitive_for_country_code(): void
    {
        $lower = PhoneNumberFormatter::assemble('gh', '244000000');
        $upper = PhoneNumberFormatter::assemble('GH', '244000000');

        $this->assertSame($upper, $lower);
    }

    public function test_assemble_handles_null_country_code(): void
    {
        $result = PhoneNumberFormatter::assemble(null, '244000000');

        $this->assertSame('244000000', $result);
    }

    public function test_assemble_prepends_us_dial_code(): void
    {
        $result = PhoneNumberFormatter::assemble('US', '2125551234');

        $this->assertSame('+12125551234', $result);
    }

    public function test_assemble_prepends_kenya_dial_code(): void
    {
        $result = PhoneNumberFormatter::assemble('KE', '722000000');

        $this->assertSame('+254722000000', $result);
    }

    // ── split ─────────────────────────────────────────────────────────────────

    public function test_split_returns_default_gh_for_empty_string(): void
    {
        [$country, $local] = PhoneNumberFormatter::split('');

        $this->assertSame('GH', $country);
        $this->assertSame('', $local);
    }

    public function test_split_returns_default_gh_for_null(): void
    {
        [$country, $local] = PhoneNumberFormatter::split(null);

        $this->assertSame('GH', $country);
        $this->assertSame('', $local);
    }

    public function test_split_extracts_ghana_from_international_number(): void
    {
        [$country, $local] = PhoneNumberFormatter::split('+233244000000');

        $this->assertSame('GH', $country);
        $this->assertSame('244000000', $local);
    }

    public function test_split_extracts_nigeria_from_international_number(): void
    {
        [$country, $local] = PhoneNumberFormatter::split('+2348012345678');

        $this->assertSame('NG', $country);
        $this->assertSame('8012345678', $local);
    }

    public function test_split_extracts_kenya_from_international_number(): void
    {
        [$country, $local] = PhoneNumberFormatter::split('+254722000000');

        $this->assertSame('KE', $country);
        $this->assertSame('722000000', $local);
    }

    public function test_split_returns_gh_with_digits_for_local_number_without_plus(): void
    {
        [$country, $local] = PhoneNumberFormatter::split('0244000000');

        $this->assertSame('GH', $country);
        $this->assertSame('0244000000', $local);
    }

    public function test_split_returns_two_element_array(): void
    {
        $result = PhoneNumberFormatter::split('+233244000000');

        $this->assertCount(2, $result);
    }

    public function test_assemble_and_split_are_inverse_operations(): void
    {
        $assembled = PhoneNumberFormatter::assemble('NG', '8012345678');
        $this->assertNotNull($assembled);

        [$country, $local] = PhoneNumberFormatter::split($assembled);

        $this->assertSame('NG', $country);
        $this->assertSame('8012345678', $local);
    }
}
