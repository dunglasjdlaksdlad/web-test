<?php

namespace App\Models\Content;


use App\Models\Dashboard_And_Reports\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CDBR extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'ma_su_co',
        'dinh_danh_su_co',
        'khu_vuc',
        'quan',
        'ma_tram',
        'thoi_gian_bat_dau',
        'thoi_gian_ket_thuc',
        'tong_thoi_gian',
        'ngay_ps_sc',
        'filter_data',
        'nn_muc_1',
        'packed',
        'status',
    ];
    static public function filterData($data, $now)
    {
        if (!CDBR::exists()) {
            return;
        }
        // $startDate = $data->start_date ? Carbon::parse($data->start_date) : Carbon::parse(CDBR::min('ngay_ps_sc'));
        // $endDate = $data->end_date ? Carbon::parse($data->end_date) : Carbon::now();
        // $startDate = Carbon::parse('2024-1-1');
        // $endDate = Carbon::parse('2024-12-1');
        // $sd = $startDate->toDateString();
        // $ed = $endDate->toDateString();
        // $result = [];
        $minDate = CDBR::min('thoi_gian_ket_thuc');
        // $startDate = Carbon::parse($data['start_date'] ?? $minDate);
        // $endDate = Carbon::parse($data['end_date'] ?? $now);
        $startDate = is_string($data['start_date'] ?? null) ? Carbon::parse($data['start_date']) : Carbon::parse($minDate);
        $endDate = is_string($data['end_date'] ?? null) ? Carbon::parse($data['end_date']) : Carbon::parse($now);
        $numDays = round($startDate->diffInDays($endDate) + 1, 0);

        $nn_muc_1 = [
            'Lỗi Ngoại vi',
            'Lỗi Thiết bị OLT',
            'Lỗi Truyền dẫn',
            'Lỗi Nguồn',
            'Lỗi Khác',
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

        $districts = District::pluck('name1')->filter(fn($value) => $value !== '')->toArray();
        // dd($districts);

        $init_values_district = District::get()->groupBy('area_name')
            ->map(
                fn($group) =>
                array_fill_keys(
                    array_merge(
                        $group->pluck('name1')->filter(fn($name) => $name !== '')->toArray(), // Lọc bỏ các giá trị rỗng
                        ['total']
                    ),
                    array_fill_keys(array_merge($nn_muc_1, ['total']), 0)
                )
            )->toArray();
        // dd($init_values_district);

        $rawQuery = '
        date(ngay_ps_sc) as day,
        WEEK(ngay_ps_sc, 3) - WEEK(DATE_FORMAT(ngay_ps_sc, "%Y-%m-01"), 3) + 1 as week,
        MONTH(ngay_ps_sc) as month,
        year(ngay_ps_sc) as year,
        khu_vuc as area_name,
        quan as quanhuyen,
        nn_muc_1 as nn_muc_1,
        count(nn_muc_1)  as eachh
        ';

        // if (
        //     Cache::get('CDBR | old_file', null) == Cache::get('CDBR | new_file') &&
        //     Cache::has('CDBR | filter_date | ' . $sd . ' | ' . $ed) &&
        //     Cache::get('CDBR | filter_date | ' . $sd . ' | ' . $ed) == ($sd . $ed)
        // ) {
        //     $sorted_filter_data = Cache::get('CDBR | filter_data | ' . $sd . ' | ' . $ed);
        //     // dd(456, $result);
        // } else {
        //     Cache::put('CDBR | filter_date | ' . $sd . ' | ' . $ed, $sd . $ed);
        //     Cache::put('CDBR | old_file', Cache::get('CDBR | new_file'));

        $filter_data = CDBR::query()
            // ->when(
            //     $data->district_ids,
            //     fn($query) => $query->whereIn('quanhuyen', $data->district_ids)
            // )
            ->whereDate('ngay_ps_sc', '>=', $startDate)
            ->whereDate('ngay_ps_sc', '<=', $endDate)
            ->whereIn('khu_vuc', $area)
            ->whereIn('quan', $districts)
            ->whereIn('nn_muc_1', $nn_muc_1)
            ->selectRaw($rawQuery)
            ->groupBy('day', 'week', 'month', 'year', 'area_name', 'quanhuyen', 'nn_muc_1')
            ->get()
            ->groupBy('area_name')
            // dd($filter_data->toArray());
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
                        $districts_group = $group->groupBy('quanhuyen')
                            ->map(fn($districts) => $calculate_total($districts))
                            ->toArray();

                        $districts_group = array_replace($init_values_district, $districts_group);
                        $districts_group['total'] = array_reduce($districts_group, function ($carry, $values) {
                            foreach ($values as $key => $value) {
                                $carry[$key] = ($carry[$key] ?? 0) + $value;
                            }
                            return $carry;
                        }, );

                        return $districts_group;
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

        $key_times = array_unique(array_merge(...array_values(array_map('array_keys', $filter_data))));

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
            'barTable' => [
                0 => [
                    'accessorKey' => 'ttkv',
                    'header' => 'TTKV',
                ],
            ],
            'barDataTable' => []
        ];


        // dd($filter_data);

        if (isset($data['district_ids'])) {
            $districts_test = District::pluck('name1', 'name')->filter(fn($value) => $value !== '')->toArray();
            $valid_district_ids = array_filter($data['district_ids'], fn($item) => isset($districts_test[$item]));
            // dd($valid_district_ids);
            $area_districts = District::whereIn('name1', array_map(fn($item) => $districts_test[$item], $valid_district_ids))
                ->get()
                ->groupBy('area_name')
                ->map(fn($group) => $group->pluck('name1', 'name1')->toArray())
                ->toArray();
        }
        foreach ($key_times as $time) {
            // dd($key_times);
            if ($time == 'total') {
                continue;
            }
            $labelFormatted = str_replace(' ', '_', $time);
            foreach ($area_name as $area) {

                if (isset($data['district_ids']) && isset($area_districts[$area])) {
                    foreach ($area_districts[$area] as $district) {

                        $barDataTable[$district][$time] = round($filter_data[$area][$time][$district]['total'], 2) ?? 0;
                        $barDataTable[$district]['ttkv'] ??= $district;

                    }
                } else {

                    $barDataTable[$area][$time] = round($filter_data[$area][$time]['total']['total'], 2) ?? 0;
                    $barDataTable[$area]['ttkv'] ??= $area;
                }
            }

            $chart_data['barTable'][] = [
                'accessorKey' => $labelFormatted,
                'header' => $time
            ];

            $chart_data['barDataTable'] = array_values($barDataTable);
        }
        $title = [
            'Lỗi Thiết bị OLT' => 'Lỗi OLT',
            'Lỗi Ngoại vi' => 'Lỗi NV',
            'Lỗi Truyền dẫn' => 'Lỗi TD',
            'Lỗi Nguồn' => 'Lỗi N',
            'Lỗi Khác' => 'Lỗi K',
        ];
        // dd($filter_data['HCM']);
        foreach ($title as $key => $value) {
            $chart_data['pie'][] = [
                'name' => $value,
                'value' => round($filter_data['HCM']['total']['total'][$key], 2) ?? 0,
            ];
        }
        // dd($chart_data, $filter_data['HCM']['total']['total']);
        return $chart_data;
    }

    private static function upsertWithRetry(array $customers): void
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                DB::transaction(function () use ($customers) {
                    DB::table('c_d_b_r_s')->upsert(
                        $customers,
                        ['uuid'],
                        ['khu_vuc', 'updated_at']
                    );
                });
                break;
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt === $maxAttempts) {
                    \Log::error("Upsert failed after $maxAttempts attempts: " . $e->getMessage());
                    break;
                }
                DB::reconnect();
                usleep(500000);
            }
        }
    }
}

