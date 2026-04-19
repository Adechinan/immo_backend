<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensualiteResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'           => $this->id,
            'location_id'  => $this->location_id,
            'datePaiement' => $this->datePaiement,
            'dateLoyer'    => $this->dateLoyer,
            'created_at'   => $this->created_at,
        ];
    }
}
