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
            'nhan_vien_thuc_hien' => $this->nhan_vien_thuc_hien,
            'thoi_diem_ket_thuc' => $this->thoi_diem_ket_thuc,
            'thoi_diem_cd_dong' => $this->thoi_diem_cd_dong,
            'danh_gia_wo_thuc_hien' => $this->danh_gia_wo_thuc_hien,
            'time_status' => $this->time_status,
            'phat' => $this->phat,
            'packed' => $this->packed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
