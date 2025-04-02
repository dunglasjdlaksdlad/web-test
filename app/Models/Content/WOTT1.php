<?php

namespace App\Models\Content;

use App\Models\Dashboard_And_Reports\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class WOTT1 extends Model
{
    use SoftDeletes;
    // protected $fillable = [
    //     'uuid',
    //     'ma_cong_viec',
    //     'ttkv',
    //     'quan',
    //     'thoi_diem_ket_thuc',
    //     'danh_gia_wo_thuc_hien',
    //     'packed',
    //     'status',
    // ];

    protected $fillable = [
        'uuid',
        'ma_cong_viec',
        'ma_tram',
        'trang_thai',
        'thoi_diem_bat_dau',
        'thoi_diem_ket_thuc',
        'thoi_diem_cd_dong',
        'danh_gia_wo_thuc_hien',
        'packed',
    ];



    static public function filterData($data)
    {
        // $startDate = $data->start_date ? Carbon::parse($data->start_date) : Carbon::parse(WOTT::min('thoi_diem_ket_thuc'));
        // $endDate = $data->end_date ? Carbon::parse($data->end_date) : Carbon::now();
        if (!WOTT::exists()) {
            return;
        }
        $startDate = Carbon::parse('2024-11-1');
        $endDate = Carbon::parse('2024-11-2');
        $numDays = round($startDate->diffInDays($endDate) + 1, 0);
        $sd = $startDate->toDateString();
        $ed = $endDate->toDateString();
        $result = [];


        $danh_gia_wo_thuc_hien = [
            'Thực hiên TH < 1 ngày',
            'Thực hiên TH < 2 ngày',
            'Thực hiện QH > 1 ngày',
            'Thực hiện QH > 3 ngày',
            'Thực hiện QH > 5 ngày',
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
                    array_fill_keys(array_merge($danh_gia_wo_thuc_hien, ['total']), 0)
                )
            )->toArray();
        // dd($init_values_district);

        $rawQuery = '
        date(thoi_diem_ket_thuc) as day,
        WEEK(thoi_diem_ket_thuc, 3) - WEEK(DATE_FORMAT(thoi_diem_ket_thuc, "%Y-%m-01"), 3) + 1 as week,
        MONTH(thoi_diem_ket_thuc) as month,
        year(thoi_diem_ket_thuc) as year,
        ttkv as area_name,
        quan as quanhuyen,
        danh_gia_wo_thuc_hien as danh_gia,
        count(danh_gia_wo_thuc_hien)  as eachh
        ';


        $filter_data = WOTT::query()
            ->whereDate('thoi_diem_ket_thuc', '>=', $startDate)
            ->whereDate('thoi_diem_ket_thuc', '<=', $endDate)
            ->whereIn('ttkv', $area)
            ->whereIn('quan', $districts)
            ->whereIn('danh_gia_wo_thuc_hien', $danh_gia_wo_thuc_hien)
            ->selectRaw($rawQuery)
            ->groupBy('day', 'week', 'month', 'year', 'ttkv', 'quan', 'danh_gia')
            ->get()
            ->groupBy('area_name')
            ->map(function ($area_group) use ($init_values_district) {
                dd($area_group);
                $calculate_total = function ($group) {
                    $total_for_group = $group->groupBy('danh_gia')
                        ->mapWithKeys(function ($reason_group, $reason) {
                            return [$reason => $reason_group->sum('eachh')];
                        });


                    $total_for_group['total'] = round(array_sum($total_for_group->toArray()), 2);
                    $total_for_group['total_wo_thuc_hien_th'] = ($total_for_group['Thực hiên TH < 1 ngày'] ?? 0) + ($total_for_group['Thực hiên TH < 2 ngày'] ?? 0);
                    $total_for_group['total_wo_thuc_hien_qh'] = ($total_for_group['Thực hiện QH > 5 ngày'] ?? 0) + ($total_for_group['Thực hiện QH > 3 ngày'] ?? 0) + ($total_for_group['Thực hiện QH > 1 ngày'] ?? 0);

                    return $total_for_group;
                };

                $init_values_district = $init_values_district[$area_group->toArray()[0]['area_name']];
                // dd($init_values_district);
                $districts_group = $area_group->groupBy('quanhuyen')
                    ->map(fn($districts) => $calculate_total($districts))
                    ->toArray();
                $districts_group = array_replace($init_values_district, $districts_group);
                // dd($districts_group);
                $districts_group['total'] = array_reduce($districts_group, function ($carry, $values) {
                    foreach ($values as $key => $value) {
                        $carry[$key] = ($carry[$key] ?? 0) + $value;
                    }
                    return $carry;
                }, );

                return $districts_group;
            })->toArray();
        // dd($filter_data);


        // $key_times =  ['total', 'total_wo_thuc_hien_th', 'Thực hiên TH < 1 ngày', 'Thực hiên TH < 2 ngày','total_wo_thuc_hien_qh', 'Thực hiện QH > 3 ngày', 'Thực hiện QH > 5 ngày'];

        $key_times = [
            'Thực hiện QH > 3 ngày' => 'QH_>_3D',
            'Thực hiện QH > 5 ngày' => 'QH_>_5D'
        ];
        foreach ($area as $area_name) {
            if (!isset($filter_data[$area_name])) {
                $filter_data[$area_name] = $init_values_district[$area_name];
                continue;
            }
            // dd($init_values_district[$value]);
            foreach ($filter_data[$area_name]['total'] as $key => $value) {
                // dd($filter_data[$area_name], $key, $value);
                $filter_data['HCM']['total'][$key] = ($filter_data['HCM']['total'][$key] ?? 0) + $value;

            }
        }
        $filter_data = array_replace($area1, $filter_data);
        // dd($filter_data,$key_times);

        //chart//
        // $area_name = $data->area_ids ? $data->area_ids : $area1;
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

        // dd(Area::pluck('name')->toArray());
        if (isset($data['district_ids'])) {
            // dd($data['district_ids']);
            $districts_test = District::pluck('name1', 'name')->filter(fn($value) => $value !== '')->toArray();
            $valid_district_ids = array_filter($data['district_ids'], fn($item) => isset($districts_test[$item]));
            // dd($valid_district_ids);
            $area_districts = District::whereIn('name1', array_map(fn($item) => $districts_test[$item], $valid_district_ids))
                ->get()
                ->groupBy('area_name')
                ->map(fn($group) => $group->pluck('name1', 'name1')->toArray())
                ->toArray();
            // dd($area_districts);
        }

        foreach ($key_times as $key => $time) {
            $labelFormatted = str_replace(' ', '_', $time);
            foreach ($area_name as $area) {

                if (isset($data['district_ids']) && isset($area_districts[$area])) {
                    foreach ($area_districts[$area] as $district) {
                        $barDataTable[$district][$time] = round($filter_data[$area][$district][$key] ?? 0, 2);
                        $barDataTable[$district]['ttkv'] ??= $district;
                    }
                } else {
                    $barDataTable[$area][$time] = round($filter_data[$area]['total'][$key], 2) ?? 0;
                    $barDataTable[$area]['ttkv'] ??= $area;
                }
            }
            $chart_data['barTable'][] = [
                'accessorKey' => $labelFormatted,
                'header' => $key
            ];

            $chart_data['barDataTable'] = array_values($barDataTable);
        }
        // dd($chart_data);
        $title = [
            'Thực hiên TH < 1 ngày' => 'TH < 1d',
            'Thực hiện QH > 3 ngày' => 'QH > 3d',
            'Thực hiên TH < 2 ngày' => 'TH < 2d',
            'Thực hiện QH > 5 ngày' => 'QH > 5d',
        ];
        // dd($filter_data);
        foreach ($title as $key => $value) {
            $chart_data['pie'][] = [
                'name' => $value,
                'value' => round($filter_data['HCM']['total'][$key], 2) ?? 0,
            ];
        }
        // dd($chart_data, $filter_data);
        return $chart_data;
    }
}
