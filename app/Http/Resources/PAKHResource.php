<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PAKHResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'ma_cong_viec' => $this->ma_cong_viec,
            'ttkv' => $this->ttkv,
            'quan' => $this->quan,
            'ma_tram' => $this->ma_tram,
            'thoi_diem_ket_thuc' => $this->thoi_diem_ket_thuc,
            'thoi_diem_cd_dong' => $this->thoi_diem_cd_dong,
            'wo_qua_han' => $this->danh_gia_wo_thuc_hien,
            'packed' => $this->packed,
            'created_at' => $this->created_at,
            'update_at' => $this->created_at,
        ];
    }
}
