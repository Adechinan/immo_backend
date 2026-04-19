<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchatResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'             => $this->id,
            'dateAchat'      => $this->dateAchat,
            'user'           => new UserResource($this->whenLoaded('user')),
            'bien_en_vente'  => $this->whenLoaded('bienEnVente'),
            'created_at'     => $this->created_at,
        ];
    }
}
