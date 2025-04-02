<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GDTTResource extends JsonResource
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
            'id' =>(string) $this->id,
            'ten_canh_bao' => $this->ten_canh_bao,
            'ma_nha_tram_chuan' => $this->ma_nha_tram_chuan,
            'tinh' => $this->tinh,
            'khu_vuc' => $this->khu_vuc,
            'quanhuyen' => $this->quanhuyen,
            'nn_muc_1' => $this->nn_muc_1,
            'cellh_sau_giam_tru' => $this->cellh_sau_giam_tru,
            'thoi_gian_xuat_hien_canh_bao' => $this->thoi_gian_xuat_hien_canh_bao,
            'thoi_gian_ket_thuc' => $this->thoi_gian_ket_thuc,
            // 'thoi_gian_ket_thuc' => $this->thoi_gian_ket_thuc->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
