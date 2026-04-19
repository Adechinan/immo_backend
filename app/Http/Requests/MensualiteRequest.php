<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MensualiteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'datePaiement' => 'required|date',
            'dateLoyer'    => 'required|numeric|min:0',
        ];
    }
}
