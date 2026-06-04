<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\CreateUserDto;
use App\Models\Tenant\Staff;
use App\Support\PhoneNumberFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('phone_country') && ! $this->has('phone_number')) {
            return;
        }

        $this->merge([
            'phone' => PhoneNumberFormatter::assemble(
                $this->input('phone_country'),
                $this->input('phone_number'),
            ),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $staff = $this->route('staff');
        $staffId = $staff instanceof \Illuminate\Database\Eloquent\Model
            ? (static function (\Illuminate\Database\Eloquent\Model $m): string {
                $k = $m->getKey();
                return is_scalar($k) ? (string) $k : 'NULL';
            })($staff)
            : 'NULL';

        $hasLinkedUser = $staff instanceof Staff && $staff->user_id !== null;
        $userAction = $this->input('user_action');

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', 'unique:staff,email,' . $staffId],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_country' => ['nullable', 'string', Rule::in(array_keys(PhoneNumberFormatter::dialCodeMap()))],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];

        if (! $hasLinkedUser) {
            if ($userAction === 'link') {
                $rules['user_id'] = ['required', 'integer', 'exists:users,id', Rule::unique('staff', 'user_id')];
            } elseif ($userAction === 'create') {
                $rules['user_email'] = ['required', 'email', 'max:255', 'unique:users,email'];
                $rules['user_password'] = ['required', 'string', 'confirmed', Password::defaults()];
                $rules['user_roles'] = ['nullable', 'array'];
                $rules['user_roles.*'] = ['exists:roles,id'];
            }
        }

        return $rules;
    }

    public function toDto(): \App\DTOs\Tenant\StaffDto
    {
        $staff = $this->route('staff');
        $hasLinkedUser = $staff instanceof Staff && $staff->user_id !== null;
        $userAction = $this->input('user_action');

        $userId = null;
        $newUser = null;

        if (! $hasLinkedUser) {
            if ($userAction === 'link' && $this->filled('user_id')) {
                $userId = $this->integer('user_id');
            } elseif ($userAction === 'create') {
                /** @var list<int|string> $rawRoles */
                $rawRoles = (array) ($this->input('user_roles', []) ?? []);

                /** @var \App\Models\Tenant\User $actor */
                $actor = $this->user();
                $branchId = $this->filled('branch_id')
                    ? $this->integer('branch_id')
                    : $actor->operational_branch_id;

                $newUser = new CreateUserDto(
                    firstName: $this->string('first_name')->toString(),
                    lastName: $this->string('last_name')->toString(),
                    email: $this->string('user_email')->toString(),
                    password: $this->string('user_password')->toString(),
                    branchId: $branchId,
                    operationalBranchId: $branchId,
                    roles: array_map(fn(int|string $v): int => (int) $v, $rawRoles),
                );
            }
        }

        return new \App\DTOs\Tenant\StaffDto(
            firstName: $this->string('first_name')->toString(),
            lastName: $this->string('last_name')->toString(),
            email: $this->filled('email') ? $this->string('email')->toString() : null,
            phone: $this->filled('phone') ? $this->string('phone')->toString() : null,
            departmentId: $this->integer('department_id'),
            positionId: $this->integer('position_id'),
            userId: $userId,
            branchId: $this->filled('branch_id') ? $this->integer('branch_id') : null,
            newUser: $newUser,
        );
    }
}
