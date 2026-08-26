<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchatResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'             => $this->id,
            'dateAchat'      => $this->dateAchat,
            'user'           => $this->user ? new UserResource($this->user) : null,
            'nom_manuel'     => $this->nom_manuel,
            'email_manuel'   => $this->email_manuel,
            'tel_manuel'     => $this->tel_manuel,
            'bien'           => new BienResource($this->whenLoaded('bien')),
            'created_at'     => $this->created_at,
        ];
    }
}
