<?php

namespace App\Models\Content;

use App\Models\Dashboard_And_Reports\Area;
use App\Models\Dashboard_And_Reports\District;
use App\Models\Dashboard_And_Reports\QLT;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WOTT extends Model
{
    use SoftDeletes;
    protected $fillable = [
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
        $query = DB::table('w_o_t_t_s as p')
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
                            WHEN FLOOR(CEIL(@completed / 24) / 24) > 4 
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
}