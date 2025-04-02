<?php

namespace App\Models\Content;

use App\Models\Dashboard_And_Reports\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class SCTD extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'ttkv',
        'huyen',
        'ma_su_co',
        // 'thoi_gian_cap_nhat_gan_nhat',
        // 'thoi_gian_day_su_co',
        // 'thoi_gian_tiep_nhan',
        // 'thoi_gian_khac_phuc',
        // 'thoi_gian_ket_thuc_xu_ly_su_co',
        // 'thoi_diem_bat_dau',
        // 'thoi_diem_ket_thuc',
        // 'thoi_gian_anh_huong_dich_vuh',
        // 'thoi_gian_khac_phuc_loi',
        'ngay_ps',
        'phan_loai',
        'loai_nn_lop_1',
        'filter_data',
        'packed',
        'status',
    ];
    static public function filterData($data, $now)
    {
        if (!SCTD::exists()) {
            return;
        }
        // dd($data);
        // $startDate = $data->start_date ? Carbon::parse($data->start_date) : Carbon::parse(SuCoTruyenDan::min('ngay_ps'));
        // $endDate = $data->end_date ? Carbon::parse($data->end_date) : Carbon::now();
        $startDate = Carbon::parse('2024-1-1');
        $endDate = Carbon::parse('2024-11-14');
        $numDays = round($startDate->diffInDays($endDate) + 1, 0);
        $sd = $startDate->toDateString();
        $ed = $endDate->toDateString();
        $result = [];

        $nn_muc_1 = [
            'Ngoại vi',
            'Lỗi bên trong phòng máy',
            'Lỗi thiết bị',
        ];

        $area = [
            'SGN' => 'SGN',
            'NSG' => 'NSG',
            'PTO' => 'PTO',
            'TSG' => 'TSG',
            'BSG' => 'BSG',
            'GĐH' => 'GĐH',
            'TĐC' => 'TĐC',
        ];
        $area1 = [
            'HCM' => 'HCM',
            'SGN' => 'SGN',
            'NSG' => 'NSG',
            'PTO' => 'PTO',
            'TSG' => 'TSG',
            'BSG' => 'BSG',
            'GĐH' => 'GĐH',
            'TĐC' => 'TĐC',
        ];
        $districts = District::pluck('name')->toArray();

        $init_values_district = District::get()->groupBy('area_name')
            ->map(
                fn($group) =>
                array_fill_keys(
                    array_merge($group->pluck('name')->toArray(), ['total']),
                    array_fill_keys(array_merge($nn_muc_1, ['total']), 0)
                )
            )->toArray();
        // dd($init_values_district);

        // $cache_value = $sd . ' | ' . $ed;
        // $oldFile = Cache::get('SCTD | old_file');
        // $newFile = Cache::get('SCTD | new_file');
        // $filterTimeKey = 'SCTD | filter_time | ' . $sd . ' | ' . $ed;
        // $filterDataKey = 'SCTD | filter_data | ' . $sd . ' | ' . $ed;

        // if ($oldFile === $newFile && Cache::get($filterTimeKey, null) === $cache_value) {
        //     $filter_data = Cache::get($filterDataKey, []);
        //     //   dd($filter_data);
        // } else {
        //     Cache::put($filterTimeKey, $cache_value);
        //     Cache::put('SCTD | old_file', $newFile);

        $rawQuery = '
            date(ngay_ps) as day,
            WEEK(ngay_ps, 3) - WEEK(DATE_FORMAT(ngay_ps, "%Y-%m-01"), 3) + 1 as week,
            MONTH(ngay_ps) as month,
            year(ngay_ps) as year,
            ttkv as area_name,
            huyen as quanhuyen,
            phan_loai as nn_muc_1,
            count(loai_nn_lop_1)  as eachh
            ';

        $filter_data = SCTD::query()
            // ->whereDate('ngay_ps', '>=', $startDate)
            // ->whereDate('ngay_ps', '<=', $endDate)
            ->whereBetween('ngay_ps', [$startDate, $endDate])
            ->whereIn('ttkv', $area)
            ->whereIn('huyen', $districts)
            ->whereIn('phan_loai', $nn_muc_1)
            ->selectRaw($rawQuery)
            ->groupBy('day', 'week', 'month', 'year', 'ttkv', 'quanhuyen', 'nn_muc_1')
            ->get()
            ->groupBy('area_name')
            ->map(function ($area_group) use ($numDays, $init_values_district) {
                // dd($area_group->toArray()[0]['area_name']);
                $calculate_total = function ($group) {
                    $totals = $group->groupBy('nn_muc_1')
                        ->mapWithKeys(fn($items, $reason) => [$reason => $items->sum('eachh')]);

                    $totals['total'] = round(array_sum($totals->toArray()), 2);
                    return $totals;
                };

                $init_values_district = $init_values_district[$area_group->toArray()[0]['area_name']];

                $area_group = $area_group->groupBy(fn($item) => match (true) {
                    $numDays <= 7 => 'D' . substr($item->day, -2) . '/' . $item->month,
                    $numDays > 7 && $numDays <= 31 => 'W' . $item->week . '/' . $item->month,
                    $numDays <= 366 => 'M' . $item->month . '/' . substr($item->year, -2),
                    // $numDays >= 365 => 'Năm ' . $item->year
                    $numDays >= 365 => 'Y' . substr($item->year, -2)
                })
                    ->sortBy(fn($items) => [$items[0]->year, $items[0]->month, $items[0]->week, $items[0]->day])
                    ->map(function ($group) use ($calculate_total, $numDays, $init_values_district) {
                        $districts_data = $group->groupBy('quanhuyen')
                            ->map(fn($districts) => $calculate_total($districts))
                            ->toArray();

                        $districts_data = array_replace($init_values_district, $districts_data);
                        $districts_data['total'] = array_reduce($districts_data, function ($carry, $values) {
                            foreach ($values as $key => $value) {
                                $carry[$key] = ($carry[$key] ?? 0) + $value;
                            }
                            return $carry;
                        }, );

                        return $districts_data;
                    })->toArray();


                $area_group['total']['total'] = array_reduce($area_group, function ($carry, $values) {
                    foreach ($values['total'] as $key => $value) {
                        $carry[$key] = ($carry[$key] ?? 0) + $value;
                    }
                    return $carry;
                }, []);

                return $area_group;
            })->toArray();
        // dd($filter_data);

        //     Cache::put($filterDataKey, $filter_data);
        // }

        $key_times = array_unique(array_merge(...array_values(array_map('array_keys', $filter_data))));
        // dd($key_times);
        // dd($key_times);
        // foreach ($area as $value) {
        //     foreach ($key_times as $value1) {
        //         $filter_data[$value][$value1] ??= $init_values_district[$value];
        //         foreach ($filter_data[$value][$value1]['total'] as $key2 => $value2) {
        //             $filter_data['HCM'][$value1]['total'][$key2] = ($filter_data['HCM'][$value1]['total'][$key2] ?? 0) + $value2;
        //         }
        //     }
        //     $filter_data[$value] = array_replace(array_fill_keys($key_times, []), $filter_data[$value]);
        // }
        // $filter_data = array_replace($area1, $filter_data);
        foreach ($area as $value) {
            if (!isset($filter_data[$value])) {
                $filter_data[$value] = [];
            }
            foreach ($key_times as $value1) {
                if (!isset($filter_data[$value][$value1])) {
                    $filter_data[$value][$value1] = $init_values_district[$value];
                }
                foreach ($filter_data[$value][$value1]['total'] as $key2 => $value2) {
                    $filter_data['HCM'][$value1]['total'][$key2] = ($filter_data['HCM'][$value1]['total'][$key2] ?? 0) + $value2;
                }
            }
            $filter_data[$value] = array_replace(array_fill_keys($key_times, []), $filter_data[$value]);
        }
        $filter_data = array_replace($area1, $filter_data);

        // dd($filter_data);

        //chart//
        $area_name = $data['area_ids'] ? $data['area_ids'] : $area1;

        $chart_data = [
            'pie' => [],
            'bar' => [],
            'barTable' => [
                0 => [
                    'accessorKey' => 'ttkv',
                    'header' => 'TTKV',
                ],
            ],
            'barDataTable' => []
        ];


        if (isset($data['district_ids'])) {
            $area_districts = District::whereIn('name', $data['district_ids'])
                ->get()
                ->groupBy('area_name')
                ->map(fn($group) => $group->pluck('name', 'name')->toArray())
                ->toArray();
        }
        // dd($filter_data);
        foreach ($key_times as $time) {
            // dd($key_times);
            if ($time == 'total') {
                continue;
            }
            $labelFormatted = str_replace(' ', '_', $time);
            foreach ($area_name as $area) {

                if (isset($data['district_ids']) && isset($area_districts[$area])) {
                    foreach ($area_districts[$area] as $district) {
                        $bar[$district] = $filter_data[$area][$time][$district]['total'] ?? 0;
                        // $barDataTable[$district][$labelFormatted] = round($filter_data[$area][$time][$district]['total'], 2) ?? 0;
                        $barDataTable[$district][$time] = round($filter_data[$area][$time][$district]['total'], 2) ?? 0;
                        $barDataTable[$district]['ttkv'] ??= $district;

                    }
                } else {
                    $bar[$area] = $filter_data[$area][$time]['total']['total'] ?? 0;
                    // $barDataTable[$area][$labelFormatted] = round($filter_data[$area][$time]['total']['total'], 2) ?? 0;
                    $barDataTable[$area][$time] = round($filter_data[$area][$time]['total']['total'], 2) ?? 0;
                    $barDataTable[$area]['ttkv'] ??= $area;
                }
            }

            $bar['name'] = $time;
            $chart_data['bar'][] = $bar;
            $chart_data['barTable'][] = [
                'accessorKey' => $labelFormatted,
                'header' => $time
            ];

            $chart_data['barDataTable'] = array_values($barDataTable);
        }
        $title = [
            'Ngoại vi' => 'Ngoại Vi',
            'Lỗi bên trong phòng máy' => 'Lỗi TPM',
            'Lỗi thiết bị' => 'Lỗi TB',
        ];
        foreach ($title as $key => $value) {
            $chart_data['pie'][] = [
                'name' => $value,
                'value' => round($filter_data['HCM']['total']['total'][$key], 2) ?? 0,
            ];
        }
        // dd($chart_data, $filter_data['HCM']['total']['total']);
        return $chart_data;
    }
}
