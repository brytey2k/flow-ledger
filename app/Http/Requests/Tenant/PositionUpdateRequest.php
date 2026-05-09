<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class PositionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $model = $this->route('position');
        $ignoreId = $model instanceof \Illuminate\Database\Eloquent\Model
            ? (static function (\Illuminate\Database\Eloquent\Model $m): string {
                $k = $m->getKey();
                return is_scalar($k) ? (string) $k : 'NULL';
            })($model)
            : 'NULL';

        return [
            'name' => ['required', 'string', 'max:100', 'unique:positions,name,' . $ignoreId],
        ];
    }

    public function toDto(): \App\DTOs\Tenant\PositionDto
    {
        return new \App\DTOs\Tenant\PositionDto(
            name: $this->string('name')->toString(),
        );
    }
}
