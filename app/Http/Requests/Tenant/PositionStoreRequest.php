<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class PositionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:positions,name'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\PositionDto
    {
        return new \App\DTOs\Tenant\PositionDto(
            name: $this->string('name')->toString(),
        );
    }
}
