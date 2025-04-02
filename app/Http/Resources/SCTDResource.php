<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SCTDResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id'=> (string) $this->id,
            'ma_su_co'=> $this->ma_su_co,
            'ttkv' => $this->ttkv,
            'huyen' => $this->huyen,
            'ngay_ps' => $this->ngay_ps,
            'phan_loai' => $this->phan_loai,
            'packed' => $this->packed,
            'created_at' => $this->created_at->format('Y/m/d h:i:s'),
        ];
    }
}
