<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'                => $this->id,
            'dateLocation'      => $this->dateLocation,
            'jour_encaissement' => $this->jour_encaissement,
            'date_fin'          => $this->date_fin,
            'user'              => $this->user ? new UserResource($this->user) : null,
            'nom_manuel'      => $this->nom_manuel,
            'email_manuel'    => $this->email_manuel,
            'tel_manuel'      => $this->tel_manuel,
            'bien'            => new BienResource($this->whenLoaded('bien')),
            'mensualites'     => MensualiteResource::collection($this->whenLoaded('mensualites')),
            'created_at'      => $this->created_at,
        ];
    }
}
