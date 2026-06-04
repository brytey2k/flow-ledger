{{--
    Reusable phone-input component.

    Props:
      $nameCountry   — name attribute for the country ISO2 select  (e.g. "phone_country")
      $nameNumber    — name attribute for the local number input    (e.g. "phone_number")
      $label         — visible label text (default: "Phone Number")
      $required      — whether the field is required (default: false)
      $valueCountry  — pre-selected ISO2 country code              (default: "GH")
      $valueNumber   — pre-filled local number digits              (default: "")
      $errorKey      — the validation error bag key to show errors  (default: "phone")
      $autofocus     — whether to autofocus the number input        (default: false)
--}}
@props([
    'nameCountry'  => 'phone_country',
    'nameNumber'   => 'phone_number',
    'label'        => 'Phone Number',
    'required'     => false,
    'valueCountry' => 'GH',
    'valueNumber'  => '',
    'errorKey'     => 'phone',
    'autofocus'    => false,
])

@php
    /** @var array<int, array{code: string, dial: string, name: string}> $countries */
    $countries = [
        ['code' => 'GH', 'dial' => '+233', 'name' => 'Ghana (+233)'],
        ['code' => 'NG', 'dial' => '+234', 'name' => 'Nigeria (+234)'],
        ['code' => 'KE', 'dial' => '+254', 'name' => 'Kenya (+254)'],
        ['code' => 'ZA', 'dial' => '+27',  'name' => 'South Africa (+27)'],
        ['code' => 'CI', 'dial' => '+225', 'name' => "Côte d'Ivoire (+225)"],
        ['code' => 'GN', 'dial' => '+224', 'name' => 'Guinea (+224)'],
        ['code' => 'SN', 'dial' => '+221', 'name' => 'Senegal (+221)'],
        ['code' => 'CM', 'dial' => '+237', 'name' => 'Cameroon (+237)'],
        ['code' => 'TZ', 'dial' => '+255', 'name' => 'Tanzania (+255)'],
        ['code' => 'UG', 'dial' => '+256', 'name' => 'Uganda (+256)'],
        ['code' => 'ET', 'dial' => '+251', 'name' => 'Ethiopia (+251)'],
        ['code' => 'EG', 'dial' => '+20',  'name' => 'Egypt (+20)'],
        ['code' => 'US', 'dial' => '+1',   'name' => 'United States (+1)'],
        ['code' => 'CA', 'dial' => '+1',   'name' => 'Canada (+1)'],
        ['code' => 'GB', 'dial' => '+44',  'name' => 'United Kingdom (+44)'],
        ['code' => 'DE', 'dial' => '+49',  'name' => 'Germany (+49)'],
        ['code' => 'FR', 'dial' => '+33',  'name' => 'France (+33)'],
        ['code' => 'IT', 'dial' => '+39',  'name' => 'Italy (+39)'],
        ['code' => 'NL', 'dial' => '+31',  'name' => 'Netherlands (+31)'],
    ];

    // old() takes precedence over the prop value passed by the parent.
    $selectedCountry = old($nameCountry, $valueCountry) ?: 'GH';
    $selectedNumber  = old($nameNumber, $valueNumber) ?: '';

    $hasError = $errors->has($nameCountry) || $errors->has($nameNumber) || $errors->has($errorKey);
@endphp

<div>
    <label class="kt-form-label block mb-2">
        {{ $label }}
        @if ($required)
            <span class="text-destructive">*</span>
        @endif
    </label>

    <div class="flex gap-2">
        {{-- Country code dropdown --}}
        <select
            name="{{ $nameCountry }}"
            id="{{ $nameCountry }}"
            class="kt-select"
            style="min-width:9rem;max-width:10rem;flex-shrink:0"
            aria-label="Country code"
        >
            @foreach ($countries as $country)
                <option
                    value="{{ $country['code'] }}"
                    {{ $selectedCountry === $country['code'] ? 'selected' : '' }}
                >
                    {{ $country['name'] }}
                </option>
            @endforeach
        </select>

        {{-- Local number input --}}
        <input
            id="{{ $nameNumber }}"
            name="{{ $nameNumber }}"
            type="tel"
            value="{{ $selectedNumber }}"
            class="kt-input w-full"
            placeholder="246227810"
            @if ($required) required @endif
            @if ($autofocus) autofocus @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        />
    </div>

    <p class="mt-1 text-xs text-muted-foreground">
        Select your country code, then enter the local number without the leading zero.
    </p>

    @error($nameCountry)
        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
    @enderror

    @error($nameNumber)
        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
    @enderror

    @error($errorKey)
        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
    @enderror
</div>
