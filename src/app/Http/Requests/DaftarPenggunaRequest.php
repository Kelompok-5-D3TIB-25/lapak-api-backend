<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NamaPeran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DaftarPenggunaRequest extends FormRequest
{
    /** Kewenangan sudah diputuskan middleware izin pada rute. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'peran' => ['sometimes', Rule::enum(NamaPeran::class)],
            'halaman' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
