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
            'categorie'   => $this->categorie,
            'surface'     => $this->surface,
            'description' => $this->description,
            'pieces'      => $this->pieces,
            'chambres'    => $this->chambres,
            'salons'      => $this->salons,
            'etage'       => $this->etage,
            'adresse'     => $this->adresse,
            'ville'       => $this->ville,
            'codePostal'  => $this->codePostal,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            // Présent uniquement quand la requête incluait near_lat/near_lng
            // (recherche "près de moi") — cf. BienController::index.
            'distance_km' => $this->distance_km !== null ? round((float) $this->distance_km, 1) : null,
            'statut'      => $this->whenLoaded('statut', fn () => [
                'id'      => $this->statut->id,
                'code'    => $this->statut->code,
                'libelle' => $this->statut->libelle,
                'couleur' => $this->statut->couleur,
            ]),
            'prix'        => $this->prix,
            'user'        => $this->whenLoaded('user', fn () => $this->user ? new UserResource($this->user) : null),
            'tofs'        => TofResource::collection($this->whenLoaded('tofs')),
            'options'     => OptionResource::collection($this->whenLoaded('options')),
            // Contrats de location/achat de ce bien (avec locataire/acheteur) — pas de LocationResource/AchatResource ici
            // pour éviter de ré-imbriquer un BienResource(bien.locations.bien) inutilement.
            'locations'   => $this->whenLoaded('locations', fn () => $this->locations->map(fn ($loc) => [
                'id'                => $loc->id,
                'dateLocation'      => $loc->dateLocation,
                'jour_encaissement' => $loc->jour_encaissement,
                'date_fin'          => $loc->date_fin,
                'user'              => $loc->user ? new UserResource($loc->user) : null,
                'nom_manuel'        => $loc->nom_manuel,
                'email_manuel'      => $loc->email_manuel,
                'tel_manuel'        => $loc->tel_manuel,
                'mensualites'       => $loc->relationLoaded('mensualites') ? MensualiteResource::collection($loc->mensualites) : [],
            ])),
            'achats'      => $this->whenLoaded('achats', fn () => $this->achats->map(fn ($achat) => [
                'id'           => $achat->id,
                'dateAchat'    => $achat->dateAchat,
                'user'         => $achat->user ? new UserResource($achat->user) : null,
                'nom_manuel'   => $achat->nom_manuel,
                'email_manuel' => $achat->email_manuel,
                'tel_manuel'   => $achat->tel_manuel,
            ])),
            'created_at'  => $this->created_at,
        ];
    }
}
