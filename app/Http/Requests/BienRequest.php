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
            'categorie'  => 'nullable|in:appartement,maison,penthouse,villa,studio',
            'surface'    => 'nullable|numeric|min:1',
            'description'=> 'nullable|string',
            'pieces'     => 'nullable|integer|min:1',
            'chambres'   => 'nullable|integer|min:0',
            'salons'     => 'nullable|integer|min:0',
            'etage'      => 'nullable|integer|min:0',
            'adresse'    => 'required|string|max:255',
            'ville'      => 'required|string|max:100',
            'codePostal' => 'nullable|string|max:10',
            'latitude'   => 'nullable|numeric|between:-90,90',
            'longitude'  => 'nullable|numeric|between:-180,180',
            'statut'     => 'nullable|in:a_vendre,a_louer,vendu,loue,reserve',
            'prix'       => 'required|numeric|min:0',
            'type_bien'  => 'nullable|in:vente,location',
            'options'    => 'nullable|array',
            'options.*'  => 'integer|exists:options,id',
            // Photos du bien — mêmes limites que le formulaire web (create.vue) :
            // 10 photos max, 5 Mo max par fichier (5120 Ko).
            'tofs'       => 'nullable|array|max:10',
            'tofs.*'     => 'nullable|image|max:5120',
        ];
    }
}
