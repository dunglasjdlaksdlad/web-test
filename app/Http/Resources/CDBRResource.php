<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CDBRResource extends JsonResource
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
            'ma_su_co' => $this->ma_su_co,
            'dinh_danh_su_co' => $this->dinh_danh_su_co,
            'khu_vuc' => $this->khu_vuc,
            'quan' => $this->quan,
            'ma_tram' => $this->ma_tram,
            'thoi_gian_bat_dau' => $this->thoi_gian_bat_dau,
            'thoi_gian_ket_thuc' => $this->thoi_gian_ket_thuc,
            'tong_thoi_gian' => $this->tong_thoi_gian,
            'ngay_ps_sc' => $this->ngay_ps_sc,
            'nn_muc_1' => $this->nn_muc_1,
            'packed' => $this->packed,
            'created_at' => $this->created_at->format('Y/m/d h:i:s'),
        ];
    }
}
