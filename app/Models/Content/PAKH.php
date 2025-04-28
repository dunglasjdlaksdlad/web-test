<?php

namespace App\Models\Content;

use App\Models\Dashboard_And_Reports\Area;
use App\Models\Dashboard_And_Reports\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Dashboard_And_Reports\QLT;

use Illuminate\Database\Query\Builder;

class PAKH extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // 'uuid',
        // 'ma_cong_viec',
        // 'ma_tram',
        // 'trang_thai',
        // 'ttkv',
        // 'quan',
        // 'thoi_diem_bat_dau',
        // 'thoi_diem_ket_thuc',
        // 'thoi_diem_cd_dong',
        // 'nhan_vien_thuc_hien',
        // 'danh_gia_wo_thuc_hien',
        // 'muc_do_uu_tien',
        // 'time_status',
        // 'phat',
        // 'packed',
        'uuid',
        'ma_cong_viec',
        'ma_tram',
        'trang_thai',
        'ttkv',
        'quan',
        'thoi_diem_bat_dau',
        'thoi_diem_ket_thuc',
        'thoi_diem_cd_dong',
        'nhan_vien_thuc_hien',
        'danh_gia_wo_thuc_hien',
        'muc_do_uu_tien',
        'time_status',
        'phat',
        'packed',
    ];

    public static function filterData(array $data): array
    {
        // if ($data) {
        //     // dd($data, $data['time']['startDate']);
        //     dd($data);
        // }
        $startDate = !empty($data['time'][0])
            ? Carbon::parse(date('Y-m-d H:i:s', $data['time'][0] / 1000))
            : Carbon::parse(self::min('thoi_diem_ket_thuc'));
        // : null;
        $endDate = !empty($data['time'][1])
            ? Carbon::parse(date('Y-m-d H:i:s', $data['time'][1] / 1000))
            : Carbon::now()->endOfMonth();
        // : null;
        // dd($startDate,$endDate);
        // $startDate = Carbon::parse('2025-4-29');
        // $endDate = Carbon::parse('2025-4-30');

        $query = self::buildMainQuery([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ttkv' => $data['ttkv'] ?? [],
            'quan' => $data['quan'] ?? [],
        ]);

        $result = $query->get()->groupBy('ttkv');
        // dd($result);

        return self::prepareChartData($result, $data);
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
                $q->whereIn('p.quan', $filters['quan']);
            });
        $query->select([
            'p.ttkv',
            'p.quan',
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

            DB::raw("
                @completed := CASE 
                    WHEN p.thoi_diem_cd_dong IS NULL THEN CEIL(TIMESTAMPDIFF(MINUTE, p.thoi_diem_ket_thuc, NOW()) / 60)
                END as completed_hours
            "),

            DB::raw("
                CASE 
                    WHEN p.thoi_diem_cd_dong IS NULL AND p.danh_gia_wo_thuc_hien IS NULL THEN
                        CASE 
                            WHEN @overdue BETWEEN -71 AND -1 THEN 'WO QH > 1 ngày'
                            WHEN @overdue BETWEEN -119 AND -72 THEN 'WO QH > 3 ngày'
                            WHEN @overdue <= -120 THEN 'WO QH > 5 ngày'
                        END 
                    ELSE p.danh_gia_wo_thuc_hien 
                END as danh_gia_wo_thuc_hien
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
                            WHEN FLOOR(CEIL(@completed / 24) / 24) > 0 
                                AND p.muc_do_uu_tien = 'Rất nghiêm trọng' 
                            THEN FLOOR(CEIL(@completed / 24) / 24) * 500000
                            WHEN FLOOR(CEIL(@completed / 24) / 24) > 5 
                                AND p.muc_do_uu_tien = 'Bình Thường' 
                            THEN (FLOOR(CEIL(@completed / 24) / 24) - 5) * 500000
                            ELSE 0
                        END
                    ELSE p.phat 
                END as phat
            "),
        ]);
        return DB::query()->fromSub($query, 'sub')->select([
            // 'sub.*',
            'sub.ttkv',
            'sub.quan',
            'sub.time_status',
            'sub.danh_gia_wo_thuc_hien',
            'sub.phat',

        ]);
    }
    private static function prepareChartData($filter, array $data): array
    {
        $chartData = [
            'pie' => [
                'TH' => ['name' => 'TH', 'value' => 0],
                'QH' => ['name' => 'QH', 'value' => 0]
            ],
            'barDataTable' => [],
            'allKeys' => [
                'Tổng WO' => 'left',
                'TH' => 'left',
                'QH' => 'left',
                'Tồn QH' => 'left',
                'Phạt' => 'right'
            ]
        ];

        $tempBar = [];
        $isDistrictFilter = isset($data['quan']);
        $areas = self::getAreas($data, $isDistrictFilter);
        // dd($areas);
        // dd($filter);
        self::processFilterData($filter, $isDistrictFilter, $tempBar, $chartData, $areas);
        // dd($chartData,$tempBar);
        return self::finalizeChartData($chartData, $areas, $tempBar, $isDistrictFilter);
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

    private static function processFilterData($filter, bool $isDistrictFilter, array &$tempBar, array &$chartData, $areas): void
    {
        if (!$isDistrictFilter) {
            $chartData['barTable'] = [['accessorKey' => 'ttkv1', 'header' => 'TTKV']];
            foreach ($filter as $ttkv => $districts) {
                $barData = [
                    'ttkv' => $ttkv,
                    'ttkv1' => $ttkv,
                    // 'FT đơn vị OS' => 0,
                    // 'Lực lượng hiệu chỉnh' => 0,
                    'Phạt' => 0,
                    'Tổng WO' => 0,
                    'TH' => 0,
                    'QH' => 0,
                    'Tồn QH' => 0
                ];
                $barData = self::processDistrictData($districts, $ttkv, $barData);
                $tempBar[$ttkv] = $barData;
                self::updatePieChartData($chartData, $barData);
            }
        } else {
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
            foreach ($filter as $ttkv => $districts) {
                // dd($areas,$filter);
                if (!is_array($areas[$ttkv])) {
                    $barData = [
                        'ttkv' => $ttkv,
                        'ttkv1' => $ttkv,
                        'quan' => null,
                        // 'FT đơn vị OS' => 0,
                        // 'Lực lượng hiệu chỉnh' => 0,
                        'Phạt' => 0,
                        'Tổng WO' => 0,
                        'TH' => 0,
                        'QH' => 0,
                        'Tồn QH' => 0,
                    ];
                    $barData = self::processDistrictData($districts, $ttkv, $barData);
                    $tempBar[$ttkv] = $barData;
                    self::updatePieChartData($chartData, $barData);
                } else {
                    foreach ($districts->groupBy('quan') as $district => $value) {
                        $barData = [
                            'ttkv' => $district,
                            'ttkv1' => $ttkv,
                            'quan' => $district,
                            // 'FT đơn vị OS' => 0,
                            // 'Lực lượng hiệu chỉnh' => 0,
                            'Phạt' => 0,
                            'Tổng WO' => 0,
                            'TH' => 0,
                            'QH' => 0,
                            'Tồn QH' => 0,
                        ];
                        $barData = self::processDistrictData($value, $district, $barData);
                        $tempBar[$district] = $barData;
                        self::updatePieChartData($chartData, $barData);
                    }
                }
            }
        }
    }

    private static function processDistrictData($districts, string $key, $barData): array
    {
        foreach ($districts->groupBy('time_status') as $status => $items) {
            // dd($districts->groupBy('time_status'));
            $count = $items->count();
            $barData[$status] = $count;
            $barData['Tổng WO'] += $count;
            $barData['Phạt'] += $items->sum('phat');
            // $barData['FT đơn vị OS'] += $items->sum('ft_don_vi_os');
            // $barData['Lực lượng hiệu chỉnh'] += $items->sum('luc_luong_hieu_chinh');
        }

        // $barData['Phạt'] = $barData['FT đơn vị OS'] + $barData['Lực lượng hiệu chỉnh'];
        // dd($barData);
        return $barData;
    }

    private static function updatePieChartData(array &$chartData, array $barData): void
    {
        $chartData['pie']['TH']['value'] += $barData['TH'];
        $chartData['pie']['QH']['value'] += $barData['QH'];
    }

    private static function finalizeChartData(array $chartData, array $areas, array $tempBar, bool $isDistrictFilter): array
    {
        $allKeys = array_keys($chartData['allKeys']);
        foreach ($allKeys as $key) {
            $chartData['barTable'][] = ['accessorKey' => $key, 'header' => $key];
        }

        $init = array_fill_keys($allKeys, 0);
        // dd($init);
        $barDataTable = [];
        // dd($tempBar);
        // dd($isDistrictFilter);

        if (!$isDistrictFilter) {
            // dd($tempBar);
            foreach ($areas as $ttkv) {
                $barDataTable[] = isset($tempBar[$ttkv])
                    ? array_replace($init, $tempBar[$ttkv])
                    : array_merge($init, ['ttkv' => $ttkv, 'ttkv1' => $ttkv]);
            }
        } else {
            foreach ($areas as $area => $districts) {
                if (is_array($districts)) {
                    // dd(123);
                    foreach ($districts as $district) {
                        $barDataTable[] = isset($tempBar[$district])
                            ? array_replace($init, $tempBar[$district])
                            : array_merge($init, ['ttkv' => $district, 'quan' => $district, 'ttkv1' => $area]);
                    }
                } else {
                    $barDataTable[] = isset($tempBar[$area])
                        ? array_replace($init, $tempBar[$area])
                        : array_merge($init, ['ttkv' => $area, 'quan' => null, 'ttkv1' => $area]);
                }
            }
        }

        $chartData['pie'] = array_values($chartData['pie']);
        $chartData['barDataTable'] = array_values($barDataTable);
        // dd($chartData);
        return $chartData;
    }


    //  public static function filterData(array $data): array
    // {
    //     $startDate = $data['start_date']
    //         ? Carbon::parse($data['start_date'])
    //         : Carbon::parse(self::min('thoi_diem_ket_thuc'));
    //     $endDate = $data['end_date']
    //         ? Carbon::parse($data['end_date'])->endOfMonth()
    //         : Carbon::now()->endOfMonth();

    //     $query = self::buildMainQuery();
    //     // dd($query->get()->toArray());
    //     $filter = self::applyFilters($query, $startDate, $endDate, $data)
    //         ->get()
    //         ->groupBy('ttkv');
    //     // dd($filter->toArray());

    //     return self::prepareChartData($filter, $data);
    // }

    // public static function buildMainQuery()
    // {
    //     $filter = DB::table(function ($query) {
    //         $query->from('p_a_k_h_s as p')
    //             ->select([
    //                 'p.ttkv',
    //                 'p.quan',
    //                 'p.thoi_diem_ket_thuc',
    //                 'p.thoi_diem_cd_dong',
    //                 'p.muc_do_uu_tien',
    //                 'p.danh_gia_wo_thuc_hien',
    //                 'p.phat',

    //                 DB::raw("
    //                     CASE 
    //                         WHEN p.thoi_diem_cd_dong IS NULL THEN
    //                             TIMESTAMPDIFF(HOUR, NOW(), p.thoi_diem_ket_thuc)
    //                     END as overdue_hours
    //                 "),
    //                 DB::raw("
    //                     CASE 
    //                         WHEN p.thoi_diem_cd_dong IS NULL THEN
    //                             CEIL(TIMESTAMPDIFF(MINUTE, p.thoi_diem_ket_thuc, NOW()) / 60)
    //                     END as completed_hours
    //                 "),
    //                 DB::raw("
    //                     CASE 
    //                         WHEN p.thoi_diem_cd_dong IS NULL THEN
    //                             TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, NOW())
    //                     END as completion_diff
    //                 "),

    //                 DB::raw("
    //                 CASE 
    //                     WHEN p.thoi_diem_cd_dong IS NULL AND p.time_status IS NULL THEN
    //                         CASE
    //                             WHEN TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, NOW()) > 0 
    //                                 THEN 'Tồn QH' 
    //                             ELSE 'TH' 
    //                         END
    //                     ELSE p.time_status 
    //                 END as time_status
    //             "),
    //             ]);
    //     }, 'sub')
    //         ->select([
    //             'sub.ttkv',
    //             'sub.quan',
    //             'sub.thoi_diem_ket_thuc',
    //             'sub.time_status',
    //             DB::raw("
    //                 CASE 
    //                     WHEN sub.thoi_diem_cd_dong IS NULL AND sub.danh_gia_wo_thuc_hien IS NULL THEN
    //                         CASE 
    //                             WHEN sub.overdue_hours BETWEEN -71 AND -1 THEN 'WO QH > 1 ngày'
    //                             WHEN sub.overdue_hours BETWEEN -119 AND -72 THEN 'WO QH > 3 ngày'
    //                             WHEN sub.overdue_hours <= -120 THEN 'WO QH > 5 ngày'
    //                             WHEN sub.overdue_hours BETWEEN 0 AND 23 THEN 'WO STH < 1 ngày'
    //                             WHEN sub.overdue_hours BETWEEN 24 AND 47 THEN 'WO STH < 2 ngày'
    //                             WHEN sub.overdue_hours >= 48 THEN 'WO STH > 2 ngày'
    //                         END 
    //                     ELSE sub.danh_gia_wo_thuc_hien 
    //                 END as danh_gia_wo_thuc_hien
    //             "),

    //             DB::raw("
    //                 CASE 
    //                     WHEN sub.thoi_diem_cd_dong IS NULL AND sub.phat IS NULL THEN
    //                         CASE 
    //                             WHEN sub.completed_hours > 0 THEN CEIL(sub.completed_hours / 24) * 50000
    //                             ELSE 0
    //                         END 
    //                         +
    //                         CASE 
    //                             WHEN FLOOR(CEIL(sub.completed_hours / 24) / 24) > 0 
    //                                 AND sub.muc_do_uu_tien = 'Rất nghiêm trọng' 
    //                             THEN FLOOR(CEIL(sub.completed_hours / 24) / 24) * 500000
    //                             WHEN FLOOR(CEIL(sub.completed_hours / 24) / 24) > 4 
    //                                 AND sub.muc_do_uu_tien = 'Bình Thường' 
    //                             THEN (FLOOR(CEIL(sub.completed_hours / 24) / 24) - 5) * 500000
    //                             ELSE 0
    //                         END
    //                     ELSE sub.phat 
    //                 END as phat
    //             "),
    //         ]);
    //     return $filter;
    // }

    // public static function PageBuildMainQuery()
    // {
    //     $filter = DB::table(function ($query) {
    //         $query->from('p_a_k_h_s as p')
    //             ->select([
    //                 'p.id',
    //                 'p.ttkv',
    //                 'p.quan',
    //                 'p.ma_cong_viec',
    //                 'p.ma_tram',
    //                 'p.thoi_diem_ket_thuc',
    //                 'p.thoi_diem_cd_dong',
    //                 'p.nhan_vien_thuc_hien',
    //                 'p.muc_do_uu_tien',
    //                 'p.danh_gia_wo_thuc_hien',
    //                 'p.phat',
    //                 'p.packed',
    //                 'p.deleted_at',
    //                 'p.created_at',
    //                 'p.updated_at',
    //                 DB::raw("
    //                     CASE 
    //                         WHEN p.thoi_diem_cd_dong IS NULL THEN
    //                             TIMESTAMPDIFF(HOUR, NOW(), p.thoi_diem_ket_thuc)
    //                     END as overdue_hours
    //                 "),
    //                 DB::raw("
    //                     CASE 
    //                         WHEN p.thoi_diem_cd_dong IS NULL THEN
    //                             CEIL(TIMESTAMPDIFF(MINUTE, p.thoi_diem_ket_thuc, NOW()) / 60)
    //                     END as completed_hours
    //                 "),
    //                 DB::raw("
    //                     CASE 
    //                         WHEN p.thoi_diem_cd_dong IS NULL THEN
    //                             TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, NOW())
    //                     END as completion_diff
    //                 "),
    //                 DB::raw("
    //                 CASE 
    //                     WHEN p.thoi_diem_cd_dong IS NULL AND p.time_status IS NULL THEN
    //                         CASE
    //                             WHEN TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, NOW()) > 0 
    //                                 THEN 'Tồn QH' 
    //                             ELSE 'TH' 
    //                         END
    //                     ELSE p.time_status 
    //                 END as time_status
    //             "),
    //             ]);
    //     }, 'sub')
    //         ->select([
    //             'sub.*',

    //             DB::raw("
    //                 CASE 
    //                     WHEN sub.thoi_diem_cd_dong IS NULL AND sub.danh_gia_wo_thuc_hien IS NULL THEN
    //                         CASE 
    //                             WHEN sub.overdue_hours BETWEEN -71 AND -1 THEN 'WO QH > 1 ngày'
    //                             WHEN sub.overdue_hours BETWEEN -119 AND -72 THEN 'WO QH > 3 ngày'
    //                             WHEN sub.overdue_hours <= -120 THEN 'WO QH > 5 ngày'
    //                             WHEN sub.overdue_hours BETWEEN 0 AND 23 THEN 'WO STH < 1 ngày'
    //                             WHEN sub.overdue_hours BETWEEN 24 AND 47 THEN 'WO STH < 2 ngày'
    //                             WHEN sub.overdue_hours >= 48 THEN 'WO STH > 2 ngày'
    //                         END 
    //                     ELSE sub.danh_gia_wo_thuc_hien 
    //                 END as danh_gia_wo_thuc_hien
    //             "),

    //             DB::raw("
    //                 CASE 
    //                     WHEN sub.thoi_diem_cd_dong IS NULL AND sub.phat IS NULL THEN
    //                         CASE 
    //                             WHEN sub.completed_hours > 0 THEN CEIL(sub.completed_hours / 24) * 50000
    //                             ELSE 0
    //                         END 
    //                         +
    //                         CASE 
    //                             WHEN FLOOR(CEIL(sub.completed_hours / 24) / 24) > 0 
    //                                 THEN FLOOR(CEIL(sub.completed_hours / 24) / 24) * 500000
    //                             WHEN FLOOR(CEIL(sub.completed_hours / 24) / 24) > 5 
    //                                 THEN (FLOOR(CEIL(sub.completed_hours / 24) / 24) - 5) * 500000
    //                             ELSE 0
    //                         END
    //                     ELSE sub.phat 
    //                 END as phat
    //             "),
    //         ]);
    //     return $filter;
    // }


    // public static function applyFilters($query, Carbon $startDate, Carbon $endDate, array $data)
    // {
    //     return $query
    //         ->when(!empty($data['ttkv']) || !empty($data['quan']), function ($query) use ($data) {
    //             $query->where(function ($q) use ($data) {
    //                 if (!empty($data['ttkv'])) {
    //                     $q->whereIn('sub.ttkv', $data['ttkv']);
    //                 }
    //                 if (!empty($data['quan'])) {
    //                     $q->orWhereIn('sub.quan', $data['quan']);
    //                 }
    //             });
    //         });
    // }

    // private static function prepareChartData($filter, array $data): array
    // {
    //     $chartData = [
    //         'pie' => [
    //             'TH' => ['name' => 'TH', 'value' => 0],
    //             'QH' => ['name' => 'QH', 'value' => 0]
    //         ],
    //         'barDataTable' => [],
    //         'allKeys' => [
    //             'Tổng WO' => 'left',
    //             'TH' => 'left',
    //             'QH' => 'left',
    //             'Tồn QH' => 'left',
    //             'Phạt' => 'right'
    //         ]
    //     ];

    //     $tempBar = [];
    //     $isDistrictFilter = isset($data['quan']);
    //     $areas = self::getAreas($data, $isDistrictFilter);
    //     // dd($areas);
    //     self::processFilterData($filter, $isDistrictFilter, $tempBar, $chartData, $areas);

    //     return self::finalizeChartData($chartData, $areas, $tempBar, $isDistrictFilter);
    // }

    // private static function getAreas(array $data, bool $isDistrictFilter): array
    // {
    //     if ($isDistrictFilter) {
    //         $areasDistricts = District::whereIn('name2', $data['quan'])
    //             ->get()
    //             ->groupBy('area_name')
    //             ->map(fn($group) => $group->pluck('name2', 'name2')->toArray())
    //             ->toArray();
    //         return !empty($data['ttkv'])
    //             ? array_replace(array_combine($data['ttkv'], $data['ttkv']), $areasDistricts)
    //             : $areasDistricts;
    //     }
    //     return isset($data['ttkv'])
    //         ? $data['ttkv']
    //         : Area::pluck('name')->toArray();
    // }

    // private static function processFilterData($filter, bool $isDistrictFilter, array &$tempBar, array &$chartData, $areas): void
    // {
    //     if (!$isDistrictFilter) {
    //         $chartData['barTable'] = [['accessorKey' => 'ttkv1', 'header' => 'TTKV']];
    //         foreach ($filter as $ttkv => $districts) {
    //             $barData = [
    //                 'ttkv' => $ttkv,
    //                 'ttkv1' => $ttkv,
    //                 // 'FT đơn vị OS' => 0,
    //                 // 'Lực lượng hiệu chỉnh' => 0,
    //                 'Phạt' => 0,
    //                 'Tổng WO' => 0,
    //                 'TH' => 0,
    //                 'QH' => 0,
    //                 'Tồn QH' => 0
    //             ];
    //             $barData = self::processDistrictData($districts, $ttkv, $barData);
    //             $tempBar[$ttkv] = $barData;
    //             self::updatePieChartData($chartData, $barData);
    //         }
    //     } else {
    //         $chartData['barTable'] = [
    //             [
    //                 'accessorKey' => 'ttkv1',
    //                 'header' => 'TTKV',
    //             ],
    //             [
    //                 'accessorKey' => 'quan',
    //                 'header' => 'Quận',
    //             ]
    //         ];
    //         foreach ($filter as $ttkv => $districts) {
    //             // dd($areas);
    //             if (!is_array($areas[$ttkv])) {
    //                 $barData = [
    //                     'ttkv' => $ttkv,
    //                     'ttkv1' => $ttkv,
    //                     'quan' => null,
    //                     // 'FT đơn vị OS' => 0,
    //                     // 'Lực lượng hiệu chỉnh' => 0,
    //                     'Phạt' => 0,
    //                     'Tổng WO' => 0,
    //                     'TH' => 0,
    //                     'QH' => 0,
    //                     'Tồn QH' => 0,
    //                 ];
    //                 $barData = self::processDistrictData($districts, $ttkv, $barData);
    //                 $tempBar[$ttkv] = $barData;
    //                 self::updatePieChartData($chartData, $barData);
    //             } else {
    //                 foreach ($districts->groupBy('quan') as $district => $value) {
    //                     $barData = [
    //                         'ttkv' => $district,
    //                         'ttkv1' => $ttkv,
    //                         'quan' => $district,
    //                         // 'FT đơn vị OS' => 0,
    //                         // 'Lực lượng hiệu chỉnh' => 0,
    //                         'Phạt' => 0,
    //                         'Tổng WO' => 0,
    //                         'TH' => 0,
    //                         'QH' => 0,
    //                         'Tồn QH' => 0,
    //                     ];
    //                     $barData = self::processDistrictData($value, $district, $barData);
    //                     $tempBar[$district] = $barData;
    //                     self::updatePieChartData($chartData, $barData);
    //                 }
    //             }
    //         }
    //     }
    // }

    // private static function processDistrictData($districts, string $key, $barData): array
    // {


    //     foreach ($districts->groupBy('time_status') as $status => $items) {
    //         // dd($districts->groupBy('time_status'));
    //         $count = $items->count();
    //         $barData[$status] = $count;
    //         $barData['Tổng WO'] += $count;
    //         $barData['Phạt'] += $items->sum('phat');
    //         // $barData['FT đơn vị OS'] += $items->sum('ft_don_vi_os');
    //         // $barData['Lực lượng hiệu chỉnh'] += $items->sum('luc_luong_hieu_chinh');
    //     }

    //     // $barData['Phạt'] = $barData['FT đơn vị OS'] + $barData['Lực lượng hiệu chỉnh'];
    //     // dd($barData);
    //     return $barData;
    // }

    // private static function updatePieChartData(array &$chartData, array $barData): void
    // {
    //     $chartData['pie']['TH']['value'] += $barData['TH'];
    //     $chartData['pie']['QH']['value'] += $barData['QH'];
    // }

    // private static function finalizeChartData(array $chartData, array $areas, array $tempBar, bool $isDistrictFilter): array
    // {
    //     $allKeys = array_keys($chartData['allKeys']);
    //     foreach ($allKeys as $key) {
    //         $chartData['barTable'][] = ['accessorKey' => $key, 'header' => $key];
    //     }

    //     $init = array_fill_keys($allKeys, 0);
    //     // dd($init);
    //     $barDataTable = [];
    //     // dd($tempBar);
    //     // dd($isDistrictFilter);

    //     if (!$isDistrictFilter) {
    //         // dd($tempBar);
    //         foreach ($areas as $ttkv) {
    //             $barDataTable[] = isset($tempBar[$ttkv])
    //                 ? array_replace($init, $tempBar[$ttkv])
    //                 : array_merge($init, ['ttkv' => $ttkv, 'ttkv1' => $ttkv]);
    //         }
    //     } else {
    //         foreach ($areas as $area => $districts) {
    //             if (is_array($districts)) {
    //                 // dd(123);
    //                 foreach ($districts as $district) {
    //                     $barDataTable[] = isset($tempBar[$district])
    //                         ? array_replace($init, $tempBar[$district])
    //                         : array_merge($init, ['ttkv' => $district, 'quan' => $district, 'ttkv1' => $area]);
    //                 }
    //             } else {
    //                 $barDataTable[] = isset($tempBar[$area])
    //                     ? array_replace($init, $tempBar[$area])
    //                     : array_merge($init, ['ttkv' => $area, 'quan' => null, 'ttkv1' => $area]);
    //             }
    //         }
    //     }

    //     $chartData['pie'] = array_values($chartData['pie']);
    //     $chartData['barDataTable'] = array_values($barDataTable);
    //     // dd($chartData);
    //     return $chartData;
    // }




    //     static public function filterData($data)
    // {
    //     $startDate = $data['start_date'] ? Carbon::parse($data['start_date']) : Carbon::parse(PAKH::min('thoi_diem_ket_thuc'));
    //     // $endDate = $data['end_date'] ? Carbon::parse($data['end_date']) : Carbon::now();
    //     $endDate = $data['end_date']
    //         ? Carbon::parse($data['end_date'])->endOfMonth()
    //         : Carbon::now()->endOfMonth();
    //     // dd($startDate, $endDate);
    //     $ttkvLogic = "COALESCE(q1.ttkv, (SELECT ttkv FROM q_l_t_s WHERE user_vt = p.nhan_vien_thuc_hien LIMIT 1))";
    //     $quanLogic = "CASE WHEN p.ma_tram IS NOT NULL THEN p.nhom_dieu_phoi ELSE q1.quan END";
    //     $filter = DB::table(function ($query) use ($ttkvLogic, $quanLogic) {
    //         $query->from('p_a_k_h_s as p')
    //             ->leftJoin('q_l_t_s as q1', 'q1.ma_tram', '=', 'p.ma_tram')
    //             ->select([
    //                 'p.ma_tram',
    //                 'p.nhom_dieu_phoi',
    //                 'p.thoi_diem_ket_thuc',
    //                 'p.thoi_diem_cd_dong',
    //                 'p.nhan_vien_thuc_hien',
    //                 'p.muc_do_uu_tien',
    //                 // 'p.packed',
    //                 // 'p.created_at',
    //                 // 'p.updated_at',
    //                 // 'p.nhom_dieu_phoi as quan',
    //                 DB::raw("COALESCE(q1.ttkv, (SELECT ttkv FROM q_l_t_s WHERE user_vt = p.nhan_vien_thuc_hien LIMIT 1)) as ttkv"),
    //                 DB::raw("CASE 
    //                 WHEN p.ma_tram IS NOT NULL 
    //                     And p.nhom_dieu_phoi   IS NOT NULL 
    //                 THEN p.nhom_dieu_phoi 
    //                 ELSE q1.quan 
    //                 END as quan"),

    //                 DB::raw("TIMESTAMPDIFF(HOUR, IFNULL(p.thoi_diem_cd_dong, NOW()), p.thoi_diem_ket_thuc) as overdue_hours"),
    //                 DB::raw("TIMESTAMPDIFF(HOUR, NOW(), p.thoi_diem_ket_thuc) as remaining_hours"),
    //                 DB::raw("CEIL(TIMESTAMPDIFF(MINUTE, p.thoi_diem_ket_thuc, IFNULL(p.thoi_diem_cd_dong, NOW())) / 60) as completed_hours"),
    //                 DB::raw("TIMESTAMPDIFF(HOUR, p.thoi_diem_ket_thuc, IFNULL(p.thoi_diem_cd_dong, NOW())) as completion_diff"),


    //             ]);
    //     }, 'sub')
    //         ->select([
    //             'sub.ma_tram',
    //             'sub.nhom_dieu_phoi',
    //             'sub.thoi_diem_ket_thuc',
    //             'sub.thoi_diem_cd_dong',
    //             'sub.nhan_vien_thuc_hien',
    //             'sub.muc_do_uu_tien',
    //             'sub.ttkv',
    //             'sub.quan',
    //             // 'sub.packed',
    //             // 'sub.created_at',
    //             // 'sub.updated_at',

    //             DB::raw("
    //         CASE 
    //             WHEN sub.overdue_hours BETWEEN -71 AND -1 THEN 'WO QH > 1 ngày'
    //             WHEN sub.overdue_hours BETWEEN -119 AND -72 THEN 'WO QH > 3 ngày'
    //             WHEN sub.overdue_hours <= -120 THEN 'WO QH > 5 ngày'
    //             WHEN sub.remaining_hours BETWEEN 0 AND 23 THEN 'WO STH < 1 ngày'
    //             WHEN sub.remaining_hours BETWEEN 24 AND 47 THEN 'WO STH < 2 ngày'
    //             WHEN sub.remaining_hours >= 48 THEN 'WO STH > 2 ngày'
    //         END as danh_gia_wo_thuc_hien
    //     "),

    //             DB::raw("
    //         CASE 
    //             WHEN sub.completed_hours > 0 THEN CEIL(sub.completed_hours / 24) * 50000
    //             ELSE 0
    //         END as ft_don_vi_os
    //     "),
    //             DB::raw("
    //         CASE 
    //             WHEN FLOOR(sub.completed_hours / 24 / 24) > 0 THEN FLOOR(sub.completed_hours / 24 / 24) * 500000
    //             WHEN FLOOR(sub.completed_hours / 24 / 24) > 5 THEN (FLOOR(sub.completed_hours / 24 / 24) - 5) * 500000
    //             ELSE 0
    //         END as luc_luong_hieu_chinh
    //     "),
    //             DB::raw("
    //         CASE 
    //             WHEN sub.thoi_diem_cd_dong IS NOT NULL THEN
    //                 CASE WHEN sub.completion_diff > 0 THEN 'QH' ELSE 'TH' END
    //             ELSE 
    //                 CASE WHEN TIMESTAMPDIFF(HOUR, sub.thoi_diem_ket_thuc, NOW()) > 0 THEN 'Tồn QH' ELSE 'TH' END
    //         END as time_status
    //     "),
    //         ])
    //         ->when(
    //             $startDate && $endDate,
    //             fn($query) =>
    //             $query->whereBetween('sub.thoi_diem_ket_thuc', [$startDate, $endDate])
    //         )
    //         ->when(!empty($data['area_ids']) || !empty($data['district_ids']), function ($query) use ($data) {
    //             $query->where(function ($q) use ($data) {
    //                 if (!empty($data['area_ids'])) {
    //                     $q->whereIn('sub.ttkv', $data['area_ids']);
    //                 }
    //                 if (!empty($data['district_ids'])) {
    //                     $q->orWhereIn('sub.quan', $data['district_ids']);
    //                 }
    //             });
    //         })
    //         // ->when(!empty($data['area_ids']), function ($query) use ($data) {
    //         //     $query->whereIn('sub.ttkv', $data['area_ids']);
    //         // })
    //         // ->when(!empty($data['district_ids']), function ($query) use ($data) {
    //         //     $query->whereIn('sub.quan', $data['district_ids']);
    //         // })
    //         ->get()
    //         ->groupBy('ttkv');

    //     $chart_data = [
    //         'pie' => [],
    //         'barTable' => [['accessorKey' => 'ttkv', 'header' => 'TTKV']],
    //         'barDataTable' => []
    //     ];
    //     $allKeys = [];
    //     $tempBar = [];

    //     $check = isset($data['district_ids']);
    //     if ($check) {
    //         $areas_districts = District::whereIn('name2', $data['district_ids'])->get()->groupBy('area_name')
    //             ->map(fn($group) => $group->pluck('name2', 'name2')->toArray())->toArray();
    //         if (!empty($data['area_ids'])) {
    //             $areas_districts = array_replace(array_combine($data['area_ids'], $data['area_ids']), $areas_districts);
    //         }
    //     }

    //     // dd($areas_districts);
    //     $areas = isset($data['district_ids']) ? $areas_districts : (isset($data['area_ids']) ? $data['area_ids'] : Area::pluck('name')->toArray());

    //     if (!$check) {
    //         foreach ($filter as $ttkv => $districts) {
    //             $barData = [
    //                 'ttkv' => $ttkv,
    //                 'FT đơn vị OS' => 0,
    //                 'Lực lượng hiệu chỉnh' => 0,
    //             ];

    //             $tempData = [];
    //             foreach ($districts->groupBy('time_status') as $key => $items) {
    //                 $tempData[$key] = $items->count();
    //                 $tempBar[$ttkv]['Tổng WO'] = ($tempBar[$ttkv]['Tổng WO'] ?? 0) + $tempData[$key];
    //                 $tempBar[$ttkv][$key] = $tempData[$key];
    //                 if ($key == 'Tồn QH') {

    //                 } else {

    //                 }
    //                 $barData['FT đơn vị OS'] += $items->sum('ft_don_vi_os');
    //                 $barData['Lực lượng hiệu chỉnh'] += $items->sum('luc_luong_hieu_chinh');
    //                 $tempBar[$ttkv]['Phạt'] = $barData['FT đơn vị OS'] + $barData['Lực lượng hiệu chỉnh'];
    //                 $allKeys[$key] ??= $key;
    //             }
    //             // $tempBar[$ttkv]['QH'] = ($tempBar[$ttkv]['QH'] ?? 0) + ($tempBar[$ttkv]['Tồn QH'] ?? 0);
    //             $barData = array_merge($tempBar[$ttkv], $barData);
    //             $tempBar[$ttkv] = $barData;
    //             $chart_data['pie'] = [
    //                 'TH' => [
    //                     'name' => 'TH',
    //                     'value' => ($chart_data['pie']['TH']['value'] ?? 0) + ($tempBar[$ttkv]['TH'] ?? 0)
    //                 ],
    //                 'QH' => [
    //                     'name' => 'QH',
    //                     'value' => ($chart_data['pie']['QH']['value'] ?? 0) + ($tempBar[$ttkv]['QH'] ?? 0)
    //                 ]
    //             ];
    //             // dd($statusCounts);
    //             // dd($tempBar);
    //         }
    //     } else {
    //         foreach ($filter as $ttkv => $districts) {
    //             foreach ($districts->groupBy('quan') as $district => $value) {
    //                 $barData = [
    //                     'ttkv' => $district,
    //                     'FT đơn vị OS' => 0,
    //                     'Lực lượng hiệu chỉnh' => 0,
    //                 ];

    //                 $tempData = [];
    //                 foreach ($value->groupBy('time_status') as $key => $items) {
    //                     $tempData[$key] = $items->count();
    //                     $tempBar[$district]['Tổng WO'] = ($tempBar[$district]['Tổng WO'] ?? 0) + $tempData[$key];
    //                     $tempBar[$district][$key] = $tempData[$key];
    //                     if ($key == 'Tồn QH') {

    //                     } else {

    //                     }
    //                     // dd($districts->groupBy('time_status') as $key => $items)
    //                     $barData['FT đơn vị OS'] += $items->sum('ft_don_vi_os');
    //                     $barData['Lực lượng hiệu chỉnh'] += $items->sum('luc_luong_hieu_chinh');
    //                     $tempBar[$district]['Phạt'] = $barData['FT đơn vị OS'] + $barData['Lực lượng hiệu chỉnh'];
    //                     $allKeys[$key] ??= $key;
    //                 }
    //                 // $tempBar[$district]['QH'] = ($tempBar[$district]['QH'] ?? 0) + ($tempBar[$district]['Tồn QH'] ?? 0);
    //                 $barData = array_merge($tempBar[$district], $barData);
    //                 $tempBar[$district] = $barData;
    //                 $chart_data['pie'] = [
    //                     'TH' => [
    //                         'name' => 'TH',
    //                         'value' => ($chart_data['pie']['TH']['value'] ?? 0) + ($tempBar[$district]['TH'] ?? 0)
    //                     ],
    //                     'QH' => [
    //                         'name' => 'QH',
    //                         'value' => ($chart_data['pie']['QH']['value'] ?? 0) + ($tempBar[$district]['QH'] ?? 0)
    //                     ]
    //                 ];
    //             }
    //         }
    //     }
    //     // dd(123, $tempData, $barData, $tempBar, $chart_data);



    //     $desiredOrder = [
    //         'WO QH > 3 ngày',
    //         'WO QH > 5 ngày',
    //         'FT đơn vị OS',
    //         'Lực lượng hiệu chỉnh'
    //     ];

    //     $chart_data['pie'] = array_values($chart_data['pie']);
    //     $chart_data['allKeys'] = [
    //         'Tổng WO' => 'left',
    //         'TH' => 'left',
    //         'QH' => 'left',
    //         'Tồn QH' => 'left',
    //         'Phạt' => 'right',
    //     ];
    //     // dd($allKeys);
    //     foreach (['Tổng WO', 'TH', 'QH', 'Tồn QH', 'Phạt'] as $key) {
    //         // $barData[$key] = $barData[$key] ?: null;
    //         $allKeys[$key] = $key;
    //         $chart_data['barTable'][$key] = ['accessorKey' => $key, 'header' => $key];
    //         // if($key == 'TH'){
    //         //     $chart_data['barTable'][$key] += $chart_data['barDataTable']->sum('TH');
    //         // }
    //     }
    //     $chart_data['barTable'] = array_values($chart_data['barTable']);
    //     $init = array_fill_keys($allKeys, 0);
    //     // dd($init);
    //     if (!$check) {
    //         foreach ($areas as $ttkv) {
    //             // $chart_data['barDataTable'][] = isset($tempBar[$ttkv]) ? $tempBar[$ttkv] : ['ttkv' => $ttkv];
    //             $chart_data['barDataTable'][] = isset($tempBar[$ttkv]) ? array_replace($init, $tempBar[$ttkv]) : array_merge($init, ['ttkv' => $ttkv]);
    //         }
    //     } else {
    //         foreach ($areas as $ttkv) {
    //             // dd($areas);
    //             if (is_array($ttkv)) {
    //                 foreach ($ttkv as $key => $district) {
    //                     // dd($district, $tempBar[$district]); 
    //                     $chart_data['barDataTable'][] = isset($tempBar[$district]) ? array_replace($init, $tempBar[$district]) : array_merge($init, ['ttkv' => $district]);
    //                 }
    //             } else {
    //                 $chart_data['barDataTable'][] = isset($tempBar[$ttkv]) ? array_replace($init, $tempBar[$ttkv]) : array_merge($init, ['ttkv' => $ttkv]);
    //             }
    //         }

    //     }
    //     $chart_data['barDataTable'] = array_values($chart_data['barDataTable']);
    //     // dd($chart_data); 
    //     return $chart_data;
    // }
}
