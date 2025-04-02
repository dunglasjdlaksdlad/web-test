<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\PAKHResource;
use App\Models\Content\PAKH;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PAKHController extends Controller
{
    // public function index(Request $request)
    // {
    //     // dd($request->all());
    //     $query = PAKH::query();

    //     if ($request->has('id')) {
    //         $query->where('id', 'like', '%' . $request->input('id') . '%');
    //     }

    //     $perPage = $request->input('per_page', 10);
    //     $data = $query->orderByDesc('id')->paginate($perPage);

    //     return Inertia::render('content/page_pakh', [
    //         'data' => PAKHResource::collection($data),
    //     ]);
    // }

    public function index(Request $request)
    {
        $data = [
            'area_ids' => $request->input('areas'),
            'district_ids' => $request->input('districts'),
            'start_date' => $request->input('startDate'),
            'end_date' => $request->input('endDate'),
            'header' => $request->input('header')
        ];
        $filteredDataRequest = array_diff_key($request->toArray(), ["page" => "", 'per_page' => '']);


        // dd($data);
        $startDate = $data['start_date'] ? Carbon::parse($data['start_date']) : Carbon::parse(PAKH::min('thoi_diem_ket_thuc'));
        // $endDate = $data['end_date'] ? Carbon::parse($data['end_date']) : Carbon::now();
        $endDate = $data['end_date']
            ? Carbon::parse($data['end_date'])->endOfMonth()
            : Carbon::now()->endOfMonth();
        // dd($filters);
        // dd($startDate, $endDate);
        // dd($data['area_ids']);
        $ttkvLogic = "COALESCE(q1.ttkv, (SELECT ttkv FROM q_l_t_s WHERE user_vt = p.nhan_vien_thuc_hien LIMIT 1))";
        $quanLogic = "CASE WHEN p.ma_tram IS NOT NULL THEN p.nhom_dieu_phoi ELSE q1.quan END";
        $filter = DB::table(function ($query) use ($ttkvLogic, $quanLogic) {
            $query->from('p_a_k_h_s as p')
                ->leftJoin('q_l_t_s as q1', 'q1.ma_tram', '=', 'p.ma_tram')
                ->select([
                    'p.id',
                    'p.ma_cong_viec',
                    'p.ma_tram',
                    'p.nhom_dieu_phoi',
                    'p.thoi_diem_ket_thuc',
                    'p.thoi_diem_cd_dong',
                    'p.nhan_vien_thuc_hien',
                    'p.muc_do_uu_tien',
                    'p.nhom_dieu_phoi as quan',
                    'p.packed',
                    'p.created_at',
                    'p.updated_at',
                    DB::raw("COALESCE(q1.ttkv, (SELECT ttkv FROM q_l_t_s WHERE user_vt = p.nhan_vien_thuc_hien LIMIT 1)) as ttkv"),
                    //     DB::raw("CASE 
                    // WHEN p.ma_tram IS NOT NULL THEN p.nhom_dieu_phoi 
                    // ELSE q1.quan 
                    // END as quan"),

                    DB::raw("TIMESTAMPDIFF(HOUR, IFNULL(p.thoi_diem_cd_dong, NOW()), p.thoi_diem_ket_thuc) as overdue_hours"),
                    DB::raw("TIMESTAMPDIFF(HOUR, NOW(), p.thoi_diem_ket_thuc) as remaining_hours"),
                    DB::raw("CEIL(TIMESTAMPDIFF(MINUTE, p.thoi_diem_ket_thuc, IFNULL(p.thoi_diem_cd_dong, NOW())) / 60) as completed_hours"),
                    DB::raw("TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, IFNULL(p.thoi_diem_cd_dong, NOW())) as completion_diff"),


                ]);
        }, 'sub')
            ->select([
                'sub.id',
                'sub.ma_cong_viec',
                'sub.ma_tram',
                'sub.nhom_dieu_phoi',
                'sub.thoi_diem_ket_thuc',
                'sub.thoi_diem_cd_dong',
                'sub.nhan_vien_thuc_hien',
                'sub.muc_do_uu_tien',
                'sub.ttkv',
                'sub.quan',
                'sub.packed',
                'sub.created_at',
                'sub.updated_at',
                DB::raw("
            CASE 
                WHEN sub.overdue_hours BETWEEN -71 AND -1 THEN 'WO QH > 1 ngày'
                WHEN sub.overdue_hours BETWEEN -119 AND -72 THEN 'WO QH > 3 ngày'
                WHEN sub.overdue_hours <= -120 THEN 'WO QH > 5 ngày'
                WHEN sub.remaining_hours BETWEEN 0 AND 23 THEN 'WO STH < 1 ngày'
                WHEN sub.remaining_hours BETWEEN 24 AND 47 THEN 'WO STH < 2 ngày'
                WHEN sub.remaining_hours >= 48 THEN 'WO STH > 2 ngày'
            END as danh_gia_wo_thuc_hien
        "),

                DB::raw("
            CASE 
                WHEN sub.completed_hours > 0 THEN CEIL(sub.completed_hours / 24) * 50000
                ELSE 0
            END as ft_don_vi_os
        "),
                DB::raw("
            CASE 
                WHEN FLOOR(sub.completed_hours / 24 / 24) > 0 THEN FLOOR(sub.completed_hours / 24 / 24) * 500000
                WHEN FLOOR(sub.completed_hours / 24 / 24) > 5 THEN (FLOOR(sub.completed_hours / 24 / 24) - 5) * 500000
                ELSE 0
            END as luc_luong_hieu_chinh
        "),
                DB::raw("
            CASE 
                WHEN sub.thoi_diem_cd_dong IS NOT NULL THEN
                    CASE WHEN sub.completion_diff > 0 THEN 'QH' ELSE 'TH' END
                ELSE 
                    CASE WHEN TIMESTAMPDIFF(HOUR, sub.thoi_diem_ket_thuc, NOW()) > 0 THEN 'Tồn QH' ELSE 'TH' END
            END as time_status
        "),
            ])
            ->when(
                $startDate && $endDate,
                fn($query) =>
                $query->whereBetween('sub.thoi_diem_ket_thuc', [$startDate, $endDate])
            )
            ->when(!empty($data['area_ids']) || !empty($data['district_ids']), function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    if (!empty($data['area_ids'])) {
                        $q->whereIn('sub.ttkv', $data['area_ids']);
                    }
                    if (!empty($data['district_ids'])) {
                        $q->orWhereIn('sub.quan', $data['district_ids']);
                    }
                });
            })
            ->when($data['header'], function ($query) use ($data) {
                $query->whereRaw("
                    CASE 
                        WHEN sub.thoi_diem_cd_dong IS NOT NULL THEN
                            CASE WHEN sub.completion_diff > 0 THEN 'QH' ELSE 'TH' END
                        ELSE 
                            CASE WHEN TIMESTAMPDIFF(HOUR, sub.thoi_diem_ket_thuc, NOW()) > 0 THEN 'Tồn QH' ELSE 'TH' END
                    END = ?
                ", [$data['header']]);
            });
        // dd($filter->orderByDesc('id')->get()->toArray());

        // dd($filter->get()->toArray());

        if ($filteredDataRequest) {
            foreach ($filteredDataRequest as $key => $value) {
                $filter->where($key, 'like', '%' . $value . '%');
            }
        }

        // Phân trang
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        // $data = $filter->paginate($perPage, ['*'], 'page', $page);
        $data = $filter->orderByDesc('id')->paginate($perPage);

        // return inertia('content/page_pakh', [
        //     'data' => $data,
        //     'filters' => $filters, // Truyền filters để sử dụng phía client nếu cần
        // ]);
        return Inertia::render('content/page_pakh', [
            'data' => PAKHResource::collection($data),
            'filters' => $data,
        ]);
    }
}
