<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'              => $this->id,
            'dateLocation'    => $this->dateLocation,
            'user'            => new UserResource($this->whenLoaded('user')),
            'bien_en_location'=> $this->whenLoaded('bienEnLocation'),
            'mensualites'     => MensualiteResource::collection($this->whenLoaded('mensualites')),
            'created_at'      => $this->created_at,
        ];
    }
}
