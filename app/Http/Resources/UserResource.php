<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'prenom'      => $this->prenom,
            'nom'         => $this->nom,
            'tel'         => $this->tel,
            'email'       => $this->email,
            'type'        => $this->type,
            'est_certifie' => $this->estCertifie(),
            'roles'       => $this->roles->pluck('name'),
            'permissions' => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
