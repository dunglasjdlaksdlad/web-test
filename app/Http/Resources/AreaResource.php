<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>(string)$this->id,
            'name'=>$this->name,
            'districts'=>$this->districts->pluck('name')->implode(', '),
            'created_by'=>$this->created_by,
            'created_at'=>$this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
