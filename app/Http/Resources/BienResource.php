<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BienResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'titre'       => $this->titre,
            'surface'     => $this->surface,
            'description' => $this->description,
            'pieces'      => $this->pieces,
            'chambres'    => $this->chambres,
            'etage'       => $this->etage,
            'adresse'     => $this->adresse,
            'ville'       => $this->ville,
            'codePostal'  => $this->codePostal,
            'statut'      => $this->statut,
            'prix'        => $this->prix,
            'tofs'        => TofResource::collection($this->whenLoaded('tofs')),
            'options'     => OptionResource::collection($this->whenLoaded('options')),
            'en_vente'    => $this->whenLoaded('bienEnVente'),
            'en_location' => $this->whenLoaded('bienEnLocation'),
            'created_at'  => $this->created_at,
        ];
    }
}
