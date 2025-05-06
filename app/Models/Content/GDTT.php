<?php

namespace App\Models\Content;

use App\Models\Dashboard_And_Reports\Area;
use App\Models\Dashboard_And_Reports\District;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GDTT extends Model
{
    use SoftDeletes;
    protected $fillable = [
        // 'uuid',
        // 'ttkv',
        // 'quan',
        // 'loai_tu',
        // 'ma_nha_tram_chuan',
        // 'ten_canh_bao',
        // 'thoi_gian_xuat_hien_canh_bao',
        // 'thoi_diem_ket_thuc',
        // 'thoi_gian_ton',
        // "cellh_sau_giam_tru",
        // 'nn_muc_1',
        // 'filter_data',
        // 'packed',

        'uuid',
        'ttkv',
        'quan',
        'loai_tu',
        'ma_tu_btsnodeb',
        'ma_nha_tram_chuan',
        'ten_canh_bao',
        'thoi_gian_xuat_hien_canh_bao',
        'thoi_diem_ket_thuc',
        'thoi_gian_ton',
        'nhom_canh_bao',
        'nguyen_nhan',
        'tram_small_cell',
        'tg_ngay',
        'tg_dem',
        'cellh_giam_tru',
        'kh_vtnetctct',
        'packed',
    ];



    static public function filterData($data, $now)
    {
        // if (!GDTT::exists()) {
        //     return;
        // }
        $minDate = GDTT::min('thoi_diem_ket_thuc');

        $startDate = !empty($data['time'][0])
            ? Carbon::parse(date('Y-m-d H:i:s', $data['time'][0] / 1000))
            : Carbon::parse(self::min('thoi_diem_ket_thuc'));
        // : null;
        $endDate = !empty($data['time'][1])
            ? Carbon::parse(date('Y-m-d H:i:s', $data['time'][1] / 1000))
            : Carbon::now()->endOfMonth();
        // $startDate = Carbon::parse('2024-1-1');
        // $endDate = Carbon::parse('2024-6-30');
        $numDays = round($startDate->diffInDays($endDate) + 1, 0);
        // dd($numDays);
        $daysCount = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $year = $currentDate->year;
            $month = $currentDate->month;

            if ($numDays > 7 && $numDays <= 31) {
                $weekOfMonth = $currentDate->isoWeek() - $currentDate->copy()->startOfMonth()->isoWeek() + 1;
                if (!isset($daysCount[$year])) {
                    $daysCount[$year] = [];
                }

                if (!isset($daysCount[$year][$month])) {
                    $daysCount[$year][$month] = [];
                }

                if (!isset($daysCount[$year][$month][$weekOfMonth])) {
                    $daysCount[$year][$month][$weekOfMonth] = 0;
                }

                $daysCount[$year][$month][$weekOfMonth]++;

            } elseif ($numDays <= 366 && $numDays >= 28) {
                if (!isset($daysCount[$year])) {
                    $daysCount[$year] = [];
                }

                if (!isset($daysCount[$year][$month])) {
                    $daysCount[$year][$month] = 0;
                }

                $daysCount[$year][$month]++;

            } else {
                if (!isset($daysCount[$year])) {
                    $daysCount[$year] = 0;
                }
                $daysCount[$year]++;
            }
            $currentDate->addDay();
        }
        // $daysCount = collect(CarbonPeriod::create($startDate, $endDate))
        //     ->groupBy(fn($date) => $date->year)
        //     ->map(fn($group) => $group->count())
        //     ->toArray();

        // dd($daysCount, $numDays);
        // dd(123);

        $query = self::buildMainQuery([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ttkv' => $data['ttkv'] ?? [],
            'quan' => $data['quan'] ?? [],
        ]);
        // dd($query->get()->groupBy('nn_muc_1'));
        // dd($query->get()->toArray());
        $result = $query->get()->groupBy('ttkv');
        return self::prepareChartData($result, $data, $numDays, $daysCount);
    }

    public static function buildMainQuery(array $filters = []): Builder
    {
        $query = DB::table('g_d_t_t_s as p')
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereBetween('p.thoi_diem_ket_thuc', [
                    $filters['start_date'],
                    $filters['end_date'],
                ]);
            })
            ->when(!empty($filters['ttkv']), function ($q) use ($filters) {
                $q->whereIn('p.ttkv', $filters['ttkv']);
            })
            ->when(!empty($filters['quan']), function ($q) use ($filters) {
                $q->whereIn('p.quan', District::whereIn('name2',$filters['quan'])->pluck('name'));
            });


        $query->leftJoin('1_bo_n_n_g_d_t_t_s as nn', 'nn.dau_vao', '=', 'p.nguyen_nhan')
            ->leftJoin('1_tram_uu_tien_g_d_t_t_s as tut', 'tut.ma_bts', '=', 'p.ma_tu_btsnodeb')
            ->leftJoin('districts as q', 'q.name', '=', 'p.quan');

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

            'p.ttkv',
            // 'p.quan',
            DB::raw("
               q.name2 as quan
            "),

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

            // DB::raw("
            //     CASE
            //         WHEN p.nhom_canh_bao = 'Mất luồng'
            //             THEN @thoi_gian_tinh * tut.cau_hinh
            //         ELSE @thoi_gian_tinh
            //     END as cellh_truoc_giam_tru
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
            //     CASE
            //         WHEN p.nhom_canh_bao = 'Mất luồng'
            //             THEN @thoi_gian_tinh * tut.cau_hinh
            //         ELSE @thoi_gian_tinh
            //     END 
            //     -
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
            //     AS DECIMAL(10,2))
            //     as cellh_sau_giam_tru
            // "),


            DB::raw("
                CAST(
                    CASE
                        WHEN p.nhom_canh_bao = 'Mất luồng'
                            THEN @thoi_gian_tinh * tut.cau_hinh
                        ELSE @thoi_gian_tinh
                    END 

                    -

                    (IFNULL(
                        NULLIF(CAST(p.cellh_giam_tru AS DECIMAL(10,2)), 0),
                        0
                    ) +
                    CASE
                        WHEN p.nhom_canh_bao = 'Tác động theo KH/CR CTCT' AND @thoi_gian_tinh < 0.5 THEN 0
                        WHEN p.nhom_canh_bao = 'Mất luồng' THEN @tg_tac_dong * tut.cau_hinh
                        ELSE @tg_tac_dong
                    END)
                AS DECIMAL(10,2)) as cellh_sau_giam_tru
            "),

        ]);
        //   $data['giam_tru_muc_tinh'] . $data['dem_trung'] . $data['tram_small_cell'],

        return DB::query()->fromSub($query, 'sub')->select([
            'sub.*',
            // 'sub.ttkv',
            // 'sub.quan',
            // 'sub.time_status',
            // 'sub.danh_gia_wo_thuc_hien',
            // 'sub.phat',

        ])->where('sub.filter', '00');


    }

    private static function prepareChartData($filter, array $data, $numDays, $daysCount): array
    {
        $chartData = [
            'pie' => [
                'thiết bị' => ['name' => 'thiết bị', 'value' => 0],
                'vhkt' => ['name' => 'vhkt', 'value' => 0],
                'truyền dẫn' => ['name' => 'truyền dẫn', 'value' => 0],
                'nguồn' => ['name' => 'nguồn', 'value' => 0],
                'tác động hệ thống' => ['name' => 'tác động hệ thống', 'value' => 0],
                'chưa rõ nguyên nhân' => ['name' => 'chưa rõ nguyên nhân', 'value' => 0],
            ],
            'barDataTable' => [],
            'allKeys' => [
                // 'Tổng WO' => 'left',
            ]
        ];

        $tempBar = [];
        $isDistrictFilter = isset($data['quan']);
        // dd($isDistrictFilter);
        $areas = self::getAreas($data, $isDistrictFilter);
        // dd($areas);
        self::processFilterData($filter, $isDistrictFilter, $tempBar, $chartData, $areas, $numDays, $daysCount);
        // dd($tempBar, $chartData);
        return self::finalizeChartData($chartData, $areas, $tempBar, $isDistrictFilter, $numDays);
    }

    private static function getAreas(array $data, bool $isDistrictFilter): array
    {
        if ($isDistrictFilter) {
            $areasDistricts = District::whereIn('name2', $data['quan'])
                ->get()
                ->groupBy('area_name')
                ->map(fn($group) => $group->pluck('name2', 'name2')->toArray())
                ->toArray();
            return !empty($data['ttkv'])
                ? array_replace(array_combine($data['ttkv'], $data['ttkv']), $areasDistricts)
                : $areasDistricts;
        }
        return isset($data['ttkv'])
            ? $data['ttkv']
            : Area::pluck('name')->toArray();
    }

    private static function processFilterData($filter, bool $isDistrictFilter, array &$tempBar, array &$chartData, $areas, $numDays, $daysCount): void
    {
        // dd(!$isDistrictFilter);
        if (!$isDistrictFilter) {
            $chartData['barTable'] = [['accessorKey' => 'ttkv1', 'header' => 'TTKV']];
            foreach ($filter as $ttkv => $districts) {
                // dd($filter->toArray(), $ttkv, $districts->toArray(), $numDays);
                $barData = [
                    'ttkv' => $ttkv,
                    'ttkv1' => $ttkv,
                ];
                $barData = self::processDistrictData($districts, $ttkv, $barData, $numDays, $chartData, $daysCount);
                $tempBar[$ttkv] = $barData;
                self::updatePieChartData($districts, $chartData, $barData);
            }
        } else {
            // dd(123);
            $chartData['barTable'] = [
                [
                    'accessorKey' => 'ttkv1',
                    'header' => 'TTKV',
                ],
                [
                    'accessorKey' => 'quan',
                    'header' => 'Quận',
                ]
            ];
            // dd($filter);
            foreach ($filter as $ttkv => $districts) {
                // dd($areas,$filter , $ttkv , $districts);
                if (!is_array($areas[$ttkv])) {
                     $barData = [
                    'ttkv' => $ttkv,
                    'ttkv1' => $ttkv,
                    'quan' => null,
                ];
                $barData = self::processDistrictData($districts, $ttkv, $barData, $numDays, $chartData, $daysCount);
                $tempBar[$ttkv] = $barData;
                self::updatePieChartData($districts, $chartData, $barData);
                } else {
                    foreach ($districts->groupBy('quan') as $district => $value) {
                        // dd($districts,$districts->groupBy('quan') , $district , $value);
                        $barData = [
                            'ttkv' => $district,
                            'ttkv1' => $ttkv,
                            'quan' => $district,
                        ];
                        $barData = self::processDistrictData($value, $district, $barData, $numDays, $chartData, $daysCount);
                        $tempBar[$district] = $barData;
                        self::updatePieChartData($value, $chartData, $barData);

                    
              
                    }
                }
            }
        }
    }

    private static function processDistrictData($districts, string $key, $barData, $numDays, array &$chartData, $daysCount): array
    {
        // dd($districts, $key, $barData, $numDays);
        if ($numDays <= 7) {
            foreach ($districts->groupBy('year') as $key => $value) {
                foreach ($value->groupBy('month') as $key1 => $value1) {
                    foreach ($value->groupBy('day') as $key2 => $value2) {
                        $title = 'D-' . $key2 . '/' . $key1 . '/' . substr($key, -2);
                        $barData[$title] ??= round($value->sum('cellh_sau_giam_tru'),2);
                        $chartData['allKeys'][$title] ??= 'left';
                    }
                }
            }
        } elseif ($numDays > 7 && $numDays <= 31) {
            foreach ($districts->groupBy('year') as $key => $value) {
                foreach ($value->groupBy('month') as $key1 => $value1) {
                    foreach ($value1->groupBy('week') as $key2 => $value2) {
                        $title = 'W-' . $key2 . '/' . $key1 . '/' . substr($key, -2);
                        $barData[$title] ??= round($value->sum('cellh_sau_giam_tru') / $daysCount[$key][$key1][$key2], 2);
                        $chartData['allKeys'][$title] ??= 'left';
                    }
                }
            }
        } elseif ($numDays <= 366) {
            foreach ($districts->groupBy('year') as $key => $value) {
                foreach ($value->groupBy('month') as $key1 => $value1) {
                    // dd($key1, $key);
                    $title = 'M-' . $key1 . '/' . substr($key, -2);
                    $barData[$title] ??= round($value->sum('cellh_sau_giam_tru') / $daysCount[$key][$key1], 2);
                    // $barData[$title] ??= $value->sum('cellh_sau_giam_tru');
                    // $barData[$title] ??= $value->sum('cellh_sau_giam_tru') / $daysCount[$key][$key1];
                    $chartData['allKeys'][$title] ??= 'left';
                }
            }
        } elseif ($numDays > 364) {
            foreach ($districts->groupBy('year') as $key => $value) {
                $title = 'Y-' . substr($key, -2);
                $barData[$title] ??= round($value->sum('cellh_sau_giam_tru') / $daysCount[$key], 2);
                $chartData['allKeys'][$title] ??= 'left';

            }
        }

        // dd($barData);
        return $barData;
    }

    private static function updatePieChartData($districts, array &$chartData, array $barData): void
    {
        foreach ($districts->groupBy('nn_muc_1') as $key => $value) {
            // dd($districts,$districts->groupBy('nn_muc_1') , $key , $value);
            $chartData['pie'][$key]['value'] += $value->sum('cellh_sau_giam_tru');
        }
    }

    private static function finalizeChartData(array $chartData, array $areas, array $tempBar, bool $isDistrictFilter, $numDays): array
    {
        $allKeys = array_keys($chartData['allKeys']);
        foreach ($allKeys as $key) {
            $chartData['barTable'][] = ['accessorKey' => $key, 'header' => $key];
        }

        $init = array_fill_keys($allKeys, 0);
        $barDataTable = [];

        if (!$isDistrictFilter) {
            // dd($tempBar);
            foreach ($areas as $ttkv) {
                $barDataTable[] = isset($tempBar[$ttkv])
                    ? array_replace($init, $tempBar[$ttkv])
                    : array_merge($init, ['ttkv' => $ttkv, 'ttkv1' => $ttkv]);
            }
            // dd($barDataTable);
        } else {
            foreach ($areas as $area => $districts) {
                if (is_array($districts)) {
                    // dd($areas,$area,$districts,$init);
                    foreach ($districts as $district) {
                        // dd($tempBar);
                        // dd(($tempBar[$district]));
                        $barDataTable[] = isset($tempBar[$district])
                            ? array_replace($init, $tempBar[$district])
                            : array_merge($init, ['ttkv' => $district, 'quan' => $district, 'ttkv1' => $area]);
                    }
                    // dd($barDataTable);
                } else {
                    $barDataTable[] = isset($tempBar[$area])
                        ? array_replace($init, $tempBar[$area])
                        : array_merge($init, ['ttkv' => $area, 'quan' => null, 'ttkv1' => $area]);
                }
            }
        }

        $nameMap = [
            'thiết bị' => 'TB',
            'vhkt' => 'VHKT',
            'truyền dẫn' => 'TD',
            'nguồn' => 'Nguồn',
            'tác động hệ thống' => 'TĐHT',
            'chưa rõ nguyên nhân' => 'CRNN',
        ];
        // dd($chartData['pie'],array_map(fn($item) => [
        //     ...$item,
        //     'name' => $nameMap[$item['name']] ?? $item['name'],
        //     'value' => $item['value'] / $numDays,
        // ], $chartData['pie']),$numDays);
        $chartData['pie'] = array_values(array_map(fn($item) => [
            ...$item,
            'name' => $nameMap[$item['name']] ?? $item['name'],
            'value' => round($item['value'] / $numDays, 4),
                // 'value' => $item['value'] / $numDays,
        ], $chartData['pie']));
        // dd($chartData)
        $chartData['barDataTable'] = array_values($barDataTable);
        // dd($chartData);
        return $chartData;
    }
}
