<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandeResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'                => $this->id,
            'bien_id'           => $this->bien_id,
            'message_demandeur' => $this->message_demandeur,
            'email_demandeur'   => $this->email_demandeur,
            'contact_demandeur' => $this->contact_demandeur,
            'bien'              => new BienResource($this->whenLoaded('bien')),
            'created_at'        => $this->created_at,
        ];
    }
}
