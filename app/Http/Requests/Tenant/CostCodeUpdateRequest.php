<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CostCodeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $model = $this->route('cost_code');
        $ignoreId = $model instanceof \Illuminate\Database\Eloquent\Model
            ? (static function (\Illuminate\Database\Eloquent\Model $m): string {
                $k = $m->getKey();
                return is_scalar($k) ? (string) $k : 'NULL';
            })($model)
            : 'NULL';

        return [
            'code' => ['required', 'string', 'max:50', 'unique:cost_codes,code,' . $ignoreId],
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\CostCodeDto
    {
        return new \App\DTOs\Tenant\CostCodeDto(
            code: $this->string('code')->toString(),
            name: $this->string('name')->toString(),
            departmentId: $this->integer('department_id'),
        );
    }
}
