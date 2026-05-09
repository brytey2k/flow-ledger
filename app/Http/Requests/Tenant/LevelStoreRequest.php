<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class LevelStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'position' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\LevelDto
    {
        return new \App\DTOs\Tenant\LevelDto(
            name: $this->string('name')->toString(),
            position: $this->integer('position'),
        );
    }
}
