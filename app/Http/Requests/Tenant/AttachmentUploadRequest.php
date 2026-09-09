<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class AttachmentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'extensions:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            ],
        ];
    }
}
