<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AchatRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'bien_id'   => 'required|integer|exists:biens,id',
            'dateAchat' => 'nullable|date',
        ];
    }
}
