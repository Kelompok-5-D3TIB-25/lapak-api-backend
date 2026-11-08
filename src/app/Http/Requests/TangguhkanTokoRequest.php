<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TangguhkanTokoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:10', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan penangguhan wajib diisi.',
            'alasan.min' => 'Alasan penangguhan minimal :min karakter.',
        ];
    }
}
