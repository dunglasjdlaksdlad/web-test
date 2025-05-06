<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\GDTTResource;
use App\Models\Content\GDTT;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GDTTController extends Controller
{
    public function index(Request $request)
    {
        // dd($request->all());
        // $excelDate = 45629.6615046296;

        // // Excel bắt đầu từ 1900-01-01, nhưng phải trừ 1 ngày do bug "năm nhuận 1900"
        // $phpDate = Carbon::createFromTimestampUTC(($excelDate - 25569) * 86400);

        // dd($phpDate->toDateTimeString());
        // dd(DB::table('1_tram_uu_tien_g_d_t_t_s')->where('ma_bts', 'HC3231')->get()->toArray());

        // $filters = $request->input('filters', []);
        $filtersRaw = $request->input('filters', []);

        if (is_string($filtersRaw)) {
            $filters = json_decode($filtersRaw, true);
        } else {
            $filters = $filtersRaw;
        }

        if (!empty($filters[3]['id']) && $filters[3]['id'] == 'header') {
            // $filters[3]['id'] = 'thoi_diem_ket_thuc';
            // //    $filters[3]['value'] = ???
            // $filters[3]['value'][0] = self::convertHeaderToDatetime($filters[3]['value'][0]);
            $parts = explode('-', $filters[3]['value'][0]);
            $prefix = $parts[0];
            $values = explode('/', $parts[1]);
            unset($filters[3]);
            if ($prefix === 'D') {
                $filters = array_merge($filters, self::convertHeader($prefix, $values, 3));
            } elseif ($prefix === 'W') {
                $filters = array_merge($filters, self::convertHeader($prefix, $values, 3));
            } elseif ($prefix === 'M') {
                $filters = array_merge($filters, self::convertHeader($prefix, $values, 2));
            } elseif ($prefix === 'Y') {
                $filters = array_merge($filters, self::convertHeader($prefix, $values, 1));
            }
        }
        // dd($filters);

        $count = -1;
        $query = DB::table('g_d_t_t_s as p');
        $query = self::applyFilters($query, $filters, $count);
        $query = self::buildSelect($query);

        if ($count >= 0) {
            $query = self::applyFilters($query, $filters, $count);
        }
        // dd($query->limit(19)->toArray());
        // dd($query->get()->toArray());
        $perPage = $request->input('per_page', 10);
        $paginatedData = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('content/page_gdtt', [
            'data' => GDTTResource::collection($paginatedData),
            'filters' => $filters,
        ]);
    }

    private static function applyFilters($query, $request, &$count): Builder
    {
        // dd($request);
        if ($request !== null) {

            foreach ($request as $key => $value) {
                // dd(
                //     $value,
                // );
                if ($count >= 0 && $key < $count) {
                    continue;
                }
                $column = $value['id'];
                if (in_array($column, ['nn_muc_1', 'cellh_truoc_giam_tru', 'cellh_giam_tru', 'cellh_sau_giam_tru', 'day', 'week', 'month', 'year']) && $count == -1) {
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
                            // dd(
                            //     Carbon::parse(!empty($val[0]) ? $val[0] : GDTT::min($column)),
                            //     Carbon::parse(!empty($val[1]) ? $val[1] : GDTT::max($column))
                            // );
                            if (is_array($val)) {
                                if (is_numeric($val[0]) || is_numeric($val[1])) {
                                    $query->whereBetween($column, [
                                        Carbon::parse(date('Y-m-d H:i:s', $val[0] / 1000)),
                                        Carbon::parse(date('Y-m-d H:i:s', $val[1] / 1000)),
                                    ]);
                                } else {
                                    $query->whereBetween($column, [
                                        Carbon::parse(!empty($val[0]) ? $val[0] : GDTT::min($column)),
                                        Carbon::parse(!empty($val[1]) ? $val[1] : GDTT::max($column)),
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

        $query->leftJoin('1_bo_n_n_g_d_t_t_s as nn', 'nn.dau_vao', '=', 'p.nguyen_nhan')
            ->leftJoin('1_tram_uu_tien_g_d_t_t_s as tut', 'tut.ma_bts', '=', 'p.ma_tu_btsnodeb');

        $query->select([
            DB::raw(
                'day(p.thoi_diem_ket_thuc) as day'
            ),
            DB::raw(
                'WEEK(p.thoi_diem_ket_thuc, 3) - WEEK(DATE_FORMAT(p.thoi_diem_ket_thuc, "%Y-%m-01"), 3) + 1 as week',
            ),
            DB::raw(
                'MONTH(p.thoi_diem_ket_thuc) as month',
            ),
            DB::raw(
                'year(p.thoi_diem_ket_thuc) as year',
            ),

            'p.id',
            'p.ttkv',
            'p.quan',
            'p.ma_tu_btsnodeb',
            'p.ma_nha_tram_chuan',
            'p.thoi_gian_xuat_hien_canh_bao',
            'p.thoi_diem_ket_thuc',
            'p.thoi_gian_ton',
            'p.packed',
            'p.deleted_at',
            'p.created_at',
            'p.updated_at',
            'tut.cau_hinh',

            DB::raw("
                CASE 
                    WHEN nn.muc_1 = '' THEN 'chưa rõ nguyên nhân'
                    ELSE LOWER(nn.muc_1)
                END as nn_muc_1
            "),
            // 'nn.giam_tru_muc_tinh',
            // 'nn.giam_tru_muc_kv',
            DB::raw("CONCAT(nn.giam_tru_muc_tinh, p.tram_small_cell) as filter"),

            DB::raw("
                @thoi_gian_tinh := CAST(CASE 
                    WHEN nn.giam_tru_muc_kv = '0'
                        THEN p.thoi_gian_ton
                    WHEN nn.giam_tru_muc_kv = 'X'    
                        THEN p.tg_ngay
                    WHEN nn.giam_tru_muc_kv = '1'
                        THEN 0
                    ELSE NULL
                END AS DECIMAL(10,2)) as thoi_gian_tinh
            "),

            DB::raw("
                @tg_tac_dong :=  CAST(CASE
                    WHEN p.kh_vtnetctct = '0'
                        THEN 0
                    WHEN p.nguyen_nhan = 'Tác động theo KH/CR CTCT' AND @thoi_gian_tinh <= 2
                        THEN @thoi_gian_tinh
                    WHEN p.nguyen_nhan = 'Tác động theo KH/CR Vtnet' AND @thoi_gian_tinh <= 4
                        THEN @thoi_gian_tinh
                    WHEN p.nguyen_nhan = 'Tác động theo KH/CR CTCT' AND @thoi_gian_tinh > 2
                        THEN 2
                    WHEN p.nguyen_nhan = 'Tác động theo KH/CR Vtnet' AND @thoi_gian_tinh > 4
                        THEN 4
                    ELSE NULL
                END AS DECIMAL(10,2)) as tg_tac_dong
            "),

            DB::raw("
                @cellh_truoc_giam_tru := CAST(
                    CASE
                        WHEN p.nhom_canh_bao = 'Mất luồng'
                            THEN @thoi_gian_tinh * tut.cau_hinh
                        ELSE @thoi_gian_tinh
                    END 
                AS DECIMAL(10,2)) as cellh_truoc_giam_tru
            "),

            DB::raw("
                @cellh_giam_tru := CAST(
                    IFNULL(
                        NULLIF(CAST(p.cellh_giam_tru AS DECIMAL(10,2)), 0),
                        0
                    ) +
                    CASE
                        WHEN p.nhom_canh_bao = 'Tác động theo KH/CR CTCT' AND @thoi_gian_tinh < 0.5 THEN 0
                        WHEN p.nhom_canh_bao = 'Mất luồng' THEN @tg_tac_dong * tut.cau_hinh
                        ELSE @tg_tac_dong
                    END
                AS DECIMAL(10,2)) as cellh_giam_tru
            "),

            DB::raw("
               CAST((@cellh_truoc_giam_tru - @cellh_giam_tru) AS DECIMAL(10,2)) as cellh_sau_giam_tru
            "),




            // DB::raw("
            //     CAST(
            //         CASE
            //             WHEN p.nhom_canh_bao = 'Mất luồng'
            //                 THEN @thoi_gian_tinh * tut.cau_hinh
            //             ELSE @thoi_gian_tinh
            //         END 
            //     AS DECIMAL(10,2)) as cellh_truoc_giam_tru
            // "),

            // DB::raw("
            //     CAST(
            //         IFNULL(
            //             NULLIF(CAST(p.cellh_giam_tru AS DECIMAL(10,2)), 0),
            //             0
            //         ) +
            //         CASE
            //             WHEN p.nhom_canh_bao = 'Tác động theo KH/CR CTCT' AND @thoi_gian_tinh < 0.5 THEN 0
            //             WHEN p.nhom_canh_bao = 'Mất luồng' THEN @tg_tac_dong * tut.cau_hinh
            //             ELSE @tg_tac_dong
            //         END
            //     AS DECIMAL(10,2)) as cellh_giam_tru
            // "),

            // DB::raw("
            //     CAST(
            //         CASE
            //             WHEN p.nhom_canh_bao = 'Mất luồng'
            //                 THEN @thoi_gian_tinh * tut.cau_hinh
            //             ELSE @thoi_gian_tinh
            //         END 

            //         -

            //         (IFNULL(
            //             NULLIF(CAST(p.cellh_giam_tru AS DECIMAL(10,2)), 0),
            //             0
            //         ) +
            //         CASE
            //             WHEN p.nhom_canh_bao = 'Tác động theo KH/CR CTCT' AND @thoi_gian_tinh < 0.5 THEN 0
            //             WHEN p.nhom_canh_bao = 'Mất luồng' THEN @tg_tac_dong * tut.cau_hinh
            //             ELSE @tg_tac_dong
            //         END)
            //     AS DECIMAL(10,2)) as cellh_sau_giam_tru
            // "),


        ]);

        return DB::query()->fromSub($query, 'sub')->select([
            'sub.*',
        ])->where('sub.filter', '00')->where('sub.deleted_at',null);

    }

    private static function convertHeader($prefix, $value, $max): array
    {
        $convertHeader = [
            'D' => [
                'day',
                'month',
                'year'
            ],
            'W' => [
                'week',
                'month',
                'year'
            ],
            'M' => [
                'month',
                'year'
            ],
            'Y' => [
                'year'
            ],
        ];
        // dd($convertHeader);
        for ($i = 3; $i < ($max + 3); $i++) {
            $filters[$i] = [
                'id' => $convertHeader[$prefix][$i - 3],
                'value' => $convertHeader[$prefix][$i - 3] == 'year' ? '20' . $value[$i - 3] : $value[$i - 3],
                'variant' => 'text',
                'operator' => 'eq',
            ];
        }

        return $filters;
    }

    public function destroy(Request $request)
    {
        // dd($request->all());
        $item = $request->input('ids', []);
        // $item = GDTT::find($id);
        if ($item == null) {
            return back();
        }
        // $item->update(['is_active' => false]);
        // $item->delete();
        GDTT::whereIn('id', $item)->delete();
        return back();
    }
}
