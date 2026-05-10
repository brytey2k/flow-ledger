<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\CashbookEntryDto;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ManualReceiptStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter an amount.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be greater than zero.',
            'entry_date.required' => 'Please enter the entry date.',
            'entry_date.date' => 'Please enter a valid date.',
            'entry_date.before_or_equal' => 'Entry date cannot be in the future.',
        ];
    }

    public function toDto(): CashbookEntryDto
    {
        return new CashbookEntryDto(
            amount: $this->string('amount')->toString(),
            entryDate: Carbon::parse($this->string('entry_date')->toString()),
            reference: $this->filled('reference') ? $this->string('reference')->toString() : null,
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
        );
    }
}
