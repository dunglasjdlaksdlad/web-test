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
            'id' => (string) $this->id,
            'ma_tu_btsnodeb' => $this->ma_tu_btsnodeb,
            'ma_nha_tram_chuan' => $this->ma_nha_tram_chuan,
            'ttkv' => $this->ttkv,
            'quan' => $this->quan,
            'nn_muc_1' => $this->nn_muc_1,
            'thoi_gian_xuat_hien_canh_bao' => $this->thoi_gian_xuat_hien_canh_bao,
            'thoi_diem_ket_thuc' => $this->thoi_diem_ket_thuc,
            'thoi_gian_ton' => $this->thoi_gian_ton,
            'cellh_truoc_giam_tru' => $this->cellh_truoc_giam_tru,
            'cellh_giam_tru' => $this->cellh_giam_tru,
            'cellh_sau_giam_tru' => $this->cellh_sau_giam_tru,
            'day' => $this->day,
            'week' => $this->week,
            'month' => $this->month,
            'year' => substr($this->year, -2),
            'packed' => $this->packed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
