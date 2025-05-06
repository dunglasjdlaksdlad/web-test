<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\WOTTResource;
use App\Models\Content\PAKH;
use App\Models\Content\WOTT;
use App\Models\Dashboard_And_Reports\District;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PAKHController extends Controller
{
    public function index(Request $request)
    {
        // dd($request->all());

        // if (!empty($request->all())) {
        //     dd($request->all());
        // }
        $filtersRaw = $request->input('filters', []);

        if (is_string($filtersRaw)) {
            $filters = json_decode($filtersRaw, true);
        } else {
            $filters = $filtersRaw;
        }
        if (!empty($filters[3]['id']) && $filters[3]['id'] == 'header') {
            $filters[3]['id'] = 'time_status';
        }
        // dd($filters);

        $count = -1;
        $query = DB::table('p_a_k_h_s as p');
        $query = self::applyFilters($query, $filters, $count);
        $query = self::buildSelect($query);

        if ($count >= 0) {
            $query = self::applyFilters($query, $filters, $count);
        }
        // dd($query->limit(19)->toArray());
        // dd($quer)
        $perPage = $request->input('per_page', 10);
        $paginatedData = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('content/page_pakh', [
            'data' => WOTTResource::collection($paginatedData),
            // 'filters' => $request->input('filters'),
            'filters' => $filters,
        ]);
    }

    private static function applyFilters($query, $request, &$count): Builder
    {
        // dd($request);
        if ($request !== null) {
            // dd(
            //     $request,
            // );
            foreach ($request as $key => $value) {
                // dd(
                //     $value,
                //     $request,
                //     $key
                // );
                if ($count >= 0 && $key < $count) {
                    continue;
                }
                $column = $value['id'];
                if (in_array($column, ['time_status', 'danh_gia_wo_thuc_hien', 'phat']) && $count == -1) {
                    $count = (int) $key;
                    break;
                }

                $val = $value['value'];
                if (empty($val)) {
                    continue;
                }
                $operator = $value['operator'];

                if (in_array($value['variant'], ['text', 'number', 'range'])) {
                    switch ($operator) {
                        case 'iLike':
                            $query->where($column, 'like', '%' . $val . '%');
                            break;

                        case 'notILike':
                            $query->where($column, 'not like', '%' . $val . '%');
                            break;

                        case 'eq':
                            $query->where($column, '=', $val);
                            break;

                        case 'ne':
                            $query->where($column, '!=', $val);
                            break;

                        case 'isEmpty':
                            $query->where(function ($q) use ($column) {
                                $q->whereNull($column)->orWhere($column, '');
                            });
                            break;

                        case 'isNotEmpty':
                            $query->where(function ($q) use ($column) {
                                $q->whereNotNull($column)->where($column, '!=', '');
                            });
                            break;
                    }
                }

                if (in_array($value['variant'], ['multiSelect', 'select'])) {
                    switch ($operator) {
                        case 'inArray':
                            $query->whereIn($column, $val);
                            break;

                        case 'notInArray':
                            $query->whereNotIn($column, $val);
                            break;

                        case 'isEmpty':
                            $query->where(function ($q) use ($column) {
                                $q->whereNull($column)->orWhere($column, '');
                            });
                            break;

                        case 'isNotEmpty':
                            $query->where(function ($q) use ($column) {
                                $q->whereNotNull($column)->where($column, '!=', '');
                            });
                            break;
                    }
                }

                if (in_array($value['variant'], ['date', 'dateRange'])) {
                    switch ($operator) {
                        case 'eq':
                            $query->where($column, '=', Carbon::parse(date('Y-m-d H:i:s', $val / 1000)));
                            break;

                        case 'ne':
                            $query->where($column, '!=', Carbon::parse(date('Y-m-d H:i:s', $val / 1000)));
                            break;

                        case 'lt':
                            $query->where($column, '<', Carbon::parse(date('Y-m-d H:i:s', $val / 1000)));
                            break;

                        case 'gt':
                            $query->where($column, '>', Carbon::parse(date('Y-m-d H:i:s', $val / 1000)));
                            break;

                        case 'lte':
                            $query->where($column, '<=', Carbon::parse(date('Y-m-d H:i:s', $val / 1000)));
                            break;

                        case 'gte':
                            $query->where($column, '>=', Carbon::parse(date('Y-m-d H:i:s', $val / 1000)));
                            break;

                        case 'isBetween':
                            if (is_array($val)) {
                                if (is_numeric($val[0]) || is_numeric($val[1])) {
                                    $query->whereBetween($column, [
                                        Carbon::parse(date('Y-m-d H:i:s', $val[0] / 1000)),
                                        Carbon::parse(date('Y-m-d H:i:s', $val[1] / 1000)),
                                    ]);
                                } else {
                                    $query->whereBetween($column, [
                                        Carbon::parse(!empty($val[0]) ? $val[0] : PAKH::min($column)),
                                        Carbon::parse(!empty($val[1]) ? $val[1] : PAKH::max($column)),
                                    ]);
                                }
                            } else {
                                $query->where($column, '>=', Carbon::parse(date('Y-m-d H:i:s', $val / 1000)));
                            }

                            break;

                        case 'isRelativeToToday':
                            $time = Carbon::parse(date('Y-m-d H:i:s', $val / 1000));
                            $now = Carbon::now();
                            if ($time->greaterThan($now)) {
                                $query->whereBetween($column, [$now, $time]);
                            } else {
                                $query->whereBetween($column, [$time, $now]);
                            }
                            break;

                        case 'isEmpty':
                            $query->where(function ($q) use ($column) {
                                $q->whereNull($column)->orWhere($column, '');
                            });
                            break;

                        case 'isNotEmpty':
                            $query->where(function ($q) use ($column) {
                                $q->whereNotNull($column)->where($column, '!=', '');
                            });
                            break;
                    }
                }
            }

            return $query;
        }
        return $query;
    }

    private static function buildSelect($query): Builder
    {
        $query->select([
            'p.id',
            'p.ttkv',
            'p.quan',
            'p.ma_cong_viec',
            'p.ma_tram',
            'p.thoi_diem_ket_thuc',
            'p.thoi_diem_cd_dong',
            'p.nhan_vien_thuc_hien',
            'p.muc_do_uu_tien',
            'p.packed',
            'p.deleted_at',
            'p.created_at',
            'p.updated_at',
            DB::raw("
                CASE 
                    WHEN p.thoi_diem_cd_dong IS NULL AND p.time_status IS NULL THEN
                        CASE
                            WHEN TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, NOW()) > 0 THEN 'Tồn QH'
                            ELSE 'STH'
                        END
                    ELSE p.time_status 
                END as time_status
            "),

            DB::raw("
                @overdue := CASE 
                    WHEN p.thoi_diem_cd_dong IS NULL THEN FLOOR(TIMESTAMPDIFF(HOUR, NOW(), p.thoi_diem_ket_thuc))
                END as overdue_hours
            "),

            // DB::raw("
            //     CASE 
            //         WHEN p.thoi_diem_cd_dong IS NULL AND p.danh_gia_wo_thuc_hien IS NULL THEN
            //             CASE 
            //                 WHEN @overdue BETWEEN -71 AND 0 THEN 'WO QH > 1 ngày'
            //                 WHEN @overdue BETWEEN -119 AND -72 THEN 'WO QH > 3 ngày'
            //                 WHEN @overdue <= -120 THEN 'WO QH > 5 ngày'
            //                 WHEN @overdue BETWEEN 1 AND 23 THEN 'WO STH < 1 ngày'
            //                 WHEN @overdue BETWEEN 24 AND 47 THEN 'WO STH < 2 ngày'
            //                 WHEN @overdue >= 48 THEN 'WO STH > 2 ngày'
            //             END 
            //             ELSE p.danh_gia_wo_thuc_hien 
            // END as danh_gia_wo_thuc_hien
            // "),

            DB::raw("
                CASE 
                    WHEN p.thoi_diem_cd_dong IS NULL AND p.danh_gia_wo_thuc_hien IS NULL THEN
                        CASE 
                            WHEN @overdue BETWEEN -71 AND -1 THEN 'WO QH > 1 ngày'
                            WHEN @overdue BETWEEN -119 AND -72 THEN 'WO QH > 3 ngày'
                            WHEN @overdue <= -120 THEN 'WO QH > 5 ngày'
                            WHEN @overdue BETWEEN 0 AND 23 THEN 'WO STH < 1 ngày'
                            WHEN @overdue BETWEEN 24 AND 47 THEN 'WO STH < 2 ngày'
                            WHEN @overdue >= 48 THEN 'WO STH > 2 ngày'
                        END 
                        ELSE p.danh_gia_wo_thuc_hien 
            END as danh_gia_wo_thuc_hien
            "),

            DB::raw("
                @completed := CASE 
                    WHEN p.thoi_diem_cd_dong IS NULL THEN CEIL(TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, NOW()))
                END as completed_hours
            "),

            DB::raw("
                CASE 
                    WHEN p.thoi_diem_cd_dong IS NULL AND p.phat IS NULL THEN
                        CASE 
                            WHEN @completed > 0 THEN CEIL(@completed / 24) * 50000
                            ELSE 0
                        END 
                        +
                        CASE 
                            WHEN FLOOR(@completed / 24) > 0 
                            THEN FLOOR(@completed / 24) * 500000
                            WHEN FLOOR(@completed / 24) > 5 
                            THEN (FLOOR(@completed / 24) - 5) * 500000
                            ELSE 0
                        END
                    ELSE p.phat 
                END as phat
            "),
        ]);
        return DB::query()->fromSub($query, 'sub')->select([
            'sub.*',
        ]);
    }
}
