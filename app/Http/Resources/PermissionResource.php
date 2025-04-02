<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public static $wrap = false;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name1' => $this->name1,
            "framework" => $this->framework,
            'created_by' => $this->created_by,
            'created_at' =>  $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
