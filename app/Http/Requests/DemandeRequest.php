<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemandeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'message_demandeur' => 'required|string',
            'email_demandeur'   => 'required|email|max:255',
            'contact_demandeur' => 'required|string|max:50',
        ];
    }
}
