<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TofResource extends JsonResource {
    public function toArray(Request $request): array {
        return ['id' => $this->id, 'src' => $this->src, 'bien_id' => $this->bien_id];
    }
}
