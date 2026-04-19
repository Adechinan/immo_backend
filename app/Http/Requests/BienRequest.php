<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BienRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'titre'      => 'required|string|max:255',
            'surface'    => 'required|numeric|min:1',
            'description'=> 'nullable|string',
            'pieces'     => 'required|integer|min:1',
            'chambres'   => 'required|integer|min:0',
            'etage'      => 'nullable|integer|min:0',
            'adresse'    => 'required|string|max:255',
            'ville'      => 'required|string|max:100',
            'codePostal' => 'required|string|max:10',
            'statut'     => 'nullable|in:disponible,vendu,loue,reserve',
            'prix'       => 'required|numeric|min:0',
            'type_bien'  => 'nullable|in:vente,location',
            'options'    => 'nullable|array',
            'options.*'  => 'integer|exists:options,id',
        ];
    }
}
