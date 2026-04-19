<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AchatRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'bien_en_vente_id' => 'required|integer|exists:biens_en_vente,id',
            'dateAchat'        => 'nullable|date',
        ];
    }
}
