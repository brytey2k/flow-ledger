<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:50'],
            'scope' => ['nullable', 'string', 'in:mine,branch'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'position_id' => ['nullable', 'integer', 'min:1'],
            'level_id' => ['nullable', 'integer', 'min:1'],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * @return array{q?: string, status?: string, type?: string, scope?: string, branch_id?: int, department_id?: int, position_id?: int, level_id?: int, role_id?: int, date_from?: string, date_to?: string}
     */
    public function filters(): array
    {
        /** @var array{q?: string, status?: string, type?: string, scope?: string, branch_id?: int, department_id?: int, position_id?: int, level_id?: int, role_id?: int, date_from?: string, date_to?: string} $filters */
        $filters = $this->validated();

        return $filters;
    }
}
