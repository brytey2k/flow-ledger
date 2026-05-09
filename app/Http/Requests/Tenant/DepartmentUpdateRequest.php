<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $model = $this->route('department');
        $ignoreId = $model instanceof \Illuminate\Database\Eloquent\Model
            ? (static function (\Illuminate\Database\Eloquent\Model $m): string {
                $k = $m->getKey();
                return is_scalar($k) ? (string) $k : 'NULL';
            })($model)
            : 'NULL';

        return [
            'name' => ['required', 'string', 'max:100', 'unique:departments,name,' . $ignoreId],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\DepartmentDto
    {
        return new \App\DTOs\Tenant\DepartmentDto(
            name: $this->string('name')->toString(),
        );
    }
}
