<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'bien_en_location_id' => 'required|integer|exists:biens_en_location,id',
            'dateLocation'        => 'nullable|date',
        ];
    }
}
