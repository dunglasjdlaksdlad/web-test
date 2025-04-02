<?php

namespace App\Models\Content;

use App\Models\Dashboard_And_Reports\District;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class GDTT extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'khu_vuc',
        'quanhuyen',
        'loai_tu',
        'ma_nha_tram_chuan',
        'ten_canh_bao',
        'thoi_gian_xuat_hien_canh_bao',
        'thoi_gian_ket_thuc',
        'thoi_gian_ton',
        "cellh_sau_giam_tru",
        'nn_muc_1',
        'filter_data',
        'packed',
        'status',
    ];



    static public function filterData($data, $now)
    {
        if (!GDTT::exists()) {
            return;
        }
        // dd($data->toArray());

        // $startDate = $data['start_date'] ? Carbon::parse($data['start_date']) : Carbon::parse(GianDoanThongTin::min('thoi_gian_ket_thuc'));
        // $endDate = $data['end_date'] ? Carbon::parse($data['end_date']) : Carbon::now();
        $minDate = GDTT::min('thoi_gian_ket_thuc');
        // $startDate = Carbon::parse($data['start_date'] ?? $minDate);
        // $endDate = Carbon::parse($data['end_date'] ?? $now);
        $startDate = is_string($data['start_date'] ?? null) ? Carbon::parse($data['start_date']) : Carbon::parse($minDate);
        $endDate = is_string($data['end_date'] ?? null) ? Carbon::parse($data['end_date']) : Carbon::parse($now);


        // $startDate = Carbon::parse('2024-1-1');
        // $endDate = Carbon::parse('2024-12-14');
        $numDays = round($startDate->diffInDays($endDate) + 1, 0);
        $sd = $startDate->toDateString();
        $ed = $endDate->toDateString();



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

            // Tiến đến ngày tiếp theo
            $currentDate->addDay();
        }
        // dump($daysCount);


        // $daysCount = collect(CarbonPeriod::create($startDate, $endDate))
        //     ->groupBy(fn($date) => $date->year)
        //     ->map(fn($group) => $group->count())
        //     ->toArray();
        // dd($daysCount);

        $nn_muc_1 = [
            'thiết bị',
            'vhkt',
            'truyền dẫn',
            'nguồn',
            'tác động hệ thống',
            'chưa rõ nguyên nhân',
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
        // $oldFile = Cache::get('GDTT | old_file');
        // $newFile = Cache::get('GDTT | new_file');
        // $filterTimeKey = 'GDTT | filter_time | ' . $sd . ' | ' . $ed;
        // $filterDataKey = 'GDTT | filter_data | ' . $sd . ' | ' . $ed;

        // if ($oldFile === $newFile && Cache::get($filterTimeKey, null) === $cache_value) {
        //     $filter_data = Cache::get($filterDataKey, []);
        //     // dd($filter_data);
        // } else {
        //     Cache::put($filterTimeKey, $cache_value);
        //     Cache::put('GDTT | old_file', $newFile);

        $rawQuery = '
            date(thoi_gian_ket_thuc) as day,
            WEEK(thoi_gian_ket_thuc, 3) - WEEK(DATE_FORMAT(thoi_gian_ket_thuc, "%Y-%m-01"), 3) + 1 as week,
            MONTH(thoi_gian_ket_thuc) as month,
            year(thoi_gian_ket_thuc) as year,
            khu_vuc as area_name,
            quanhuyen,
            LOWER(nn_muc_1) as nn_muc_1,
            SUM(cellh_sau_giam_tru) as eachh
            ';

        $filter_data = GDTT::query()
            ->whereBetween('thoi_gian_ket_thuc', [$startDate, $endDate])
            ->whereIn('khu_vuc', $area)
            ->whereIn('quanhuyen', $districts)
            ->where('filter_data', '010')
            ->whereIn('nn_muc_1', $nn_muc_1)
            ->selectRaw($rawQuery)
            ->groupBy('day', 'week', 'month', 'year', 'area_name', 'quanhuyen', 'nn_muc_1')
            ->get()
            ->groupBy('area_name')
            ->map(function ($area_group) use ($nn_muc_1, $numDays, $daysCount, $init_values_district) {
                // dd($area_group->toArray());
                $calculate_total = function ($group, $num_days) {
                    $totals = $group->groupBy('nn_muc_1')
                        ->mapWithKeys(fn($items, $reason) => [$reason => $items->sum('eachh') / $num_days]);

                    $totals['total'] = round(array_sum($totals->toArray()), 2);
                    $totals['num_days'] = $num_days;
                    return $totals;
                };

                $init_values_district = array_fill_keys(
                    array_unique(array_merge($area_group->pluck('quanhuyen')->toArray(), ['total'])),
                    array_fill_keys(array_merge($nn_muc_1, ['total', 'num_days']), 0)
                );
                // dd($init_values_district);
                $area_group = $area_group->groupBy(fn($item) => match (true) {
                    $numDays <= 7 => 'D' . substr($item->day, -2) . '/' . $item->month,
                    $numDays > 7 && $numDays <= 31 => 'W' . $item->week . '/' . $item->month,
                    $numDays <= 366 => 'M' . $item->month . '_' . substr($item->year, -2),
                    // $numDays >= 365 => 'Năm ' . $item->year
                    $numDays >= 365 => 'Y' . substr($item->year, -2)
                })
                    ->sortBy(fn($items) => [$items[0]->year, $items[0]->month, $items[0]->week, $items[0]->day])
                    ->map(function ($group) use ($calculate_total, $numDays, $init_values_district, $daysCount) {
                    $num_days = match (true) {
                        $numDays <= 7 => 1,
                        $numDays > 7 && $numDays <= 31 => $daysCount[$group[0]['year']][$group[0]['month']][$group[0]['week']],
                        $numDays <= 366 && $numDays >= 28 => $daysCount[$group[0]['year']][$group[0]['month']],
                        $numDays >= 365 => $daysCount[$group[0]['year']],
                    };

                    $districts_data = $group->groupBy('quanhuyen')
                        ->map(fn($districts) => $calculate_total($districts, $num_days))
                        ->toArray();

                    $districts_data = array_replace($init_values_district, $districts_data);
                    $districts_data['total'] = array_reduce($districts_data, function ($carry, $values) {
                        foreach ($values as $key => $value) {
                            if ($key !== 'num_days') {
                                $carry[$key] = ($carry[$key] ?? 0) + $value;
                            }
                        }
                        return $carry;
                    }, ['num_days' => $num_days]);

                    // dd($districts_data);
                    return $districts_data;
                })->toArray();

                $area_group['total']['total'] = array_reduce($area_group, function ($carry, $values) {
                    foreach ($values['total'] as $key => $value) {
                        if ($key !== 'num_days') {
                            $carry[$key] = ($carry[$key] ?? 0) + $value * $values['total']['num_days'];
                        }
                    }
                    return $carry;
                }, []);
                // dd($area_group);
                $area_group['total']['total'] = array_map(
                    fn($value) => round($value / $numDays, 2),
                    $area_group['total']['total']
                );

                return $area_group;
            })->toArray();

        // dd($filter_data);

        //     Cache::put($filterDataKey, $filter_data);
        // }

        $key_times = array_unique(array_merge(...array_values(array_map('array_keys', $filter_data))));
        // dd($key_times);
        foreach ($area as $value) {
            if (!isset($filter_data[$value])) {
                $filter_data[$value] = [];
            }
            foreach ($key_times as $value1) {
                if (!isset($filter_data[$value][$value1])) {
                    $filter_data[$value][$value1] = $init_values_district[$value];
                }
                foreach ($filter_data[$value][$value1]['total'] as $key2 => $value2) {
                    // if($key2 == 'total'){
                    //     continue;
                    // }
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
            'thiết bị' => 'TB',
            'chưa rõ nguyên nhân' => 'CRNN',
            'truyền dẫn' => 'TD',
            'nguồn' => 'Nguồn',
            'tác động hệ thống' => 'TDHT',
            'vhkt' => 'VHKT',
        ];
        foreach ($title as $key => $value) {
            $chart_data['pie'][] = [
                'name' => $value,
                'value' => round($filter_data['HCM']['total']['total'][$key], 2) ?? 0,
            ];
        }
        // dd($chart_data, $filter_data['HCM']['total']['total']);
        return $chart_data;
        // return [
        //     'result_areas' => $chart_data['labels'],

        //     'result_pie' => $transformed,

        //     'result_columns' => ['chartData' => $chartData, 'chartConfig' => $chartConfig, 'dataTableBar' => $dataTableBar],
        // ];
    }
}
