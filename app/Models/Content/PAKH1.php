<?php

namespace App\Models\Content;

use App\Models\Dashboard_And_Reports\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class PAKH1 extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'ma_cong_viec',
        'ttkv',
        'quan',
        'thoi_diem_ket_thuc',
        'wo_qua_han',
        'loai_wo',
        'packed',
        'status',
    ];
    static public function filterData($data)
    {
        // $startDate = $data['start_date'] ? Carbon::parse($data['start_date']) : Carbon::now()->startOfMonth();
        // $endDate = $data['end_date'] ? Carbon::parse($data['end_date']) : Carbon::now();
        // $startDate = $data['start_date'] ? Carbon::parse($data['start_date']) : Carbon::parse(PAKH::min('thoi_diem_ket_thuc'));
        $startDate = Carbon::parse('2024-1-1');
        $endDate = $data['end_date'] ? Carbon::parse($data['end_date']) : Carbon::now();
        if (!PAKH::exists()) {
            return;
        }

        // $startDate = Carbon::parse('2024-1-1');
        // $endDate = Carbon::parse('2024-12-1');
        $numDays = round($startDate->diffInDays($endDate) + 1, 0);
        $sd = $startDate->toDateString();
        $ed = $endDate->toDateString();
        $result = [];

        $nn_muc_1 = [
            'Xe kéo đứt cáp' => '1',
            'Dân tác động' => '2',
            'Chập điện cháy cáp' => '3',
            'Lỗi sợi trong cáp' => '4',
            'Đứt sợi trong mx' => '5',
            'Sóc cắn' => '6',
            'Công trường, công trình thi công' => '7',
            'Đối tác tác động' => '8',
            'Đứt do cột, cây gãy đỗ' => '9',
            'Do thiên nhiên' => '10',
            'Đứt cáp băng sông' => '11',
            'Lỗi PC, ODF' => '12',
            'Lỗi thiết bị' => '13',
            'Sự cố trên cáp đối tác' => '14',
            'Sự cố tổng trạm' => '15',
            // 'Đứt cáp chôn' => '16',
        ];

        $nn_muc_2 = [
            'Ngoại vi',
            'Lỗi bên trong phòng máy',
            'Lỗi thiết bị',
        ];

        $danh_gia_wo_thuc_hien = [
            'Trong hạn',
            'Quá hạn',
        ];

        $nn_muc_1_flip = array_flip($nn_muc_1);

        $area = [
            'SGN',
            'NSG',
            'PTO',
            'TSG',
            'BSG',
            'GĐH',
            'TĐC',
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
        wo_qua_han as danh_gia,
        count(loai_wo)  as eachh
        ';

        // if (
        //     Cache::get('PAKH | old_file', null) == Cache::get('PAKH | new_file') &&
        //     Cache::has('PAKH | filter_date | ' . $sd . ' | ' . $ed) &&
        //     Cache::get('PAKH | filter_date | ' . $sd . ' | ' . $ed) == ($sd . $ed)
        // ) {
        //     $sorted_filter_data = Cache::get('PAKH | filter_data | ' . $sd . ' | ' . $ed);
        //     // dd(456, $result);
        // } else {
        //     Cache::put('PAKH | filter_date | ' . $sd . ' | ' . $ed, $sd . $ed);
        //     Cache::put('PAKH | old_file', Cache::get('PAKH | new_file'));

        $filter_data = PAKH::query()
            ->whereIn('ttkv', $area)
            // ->when(
            //     $data->district_ids,
            //     fn($query) => $query->whereIn('quan', $data->district_ids)
            // )
            ->whereDate('thoi_diem_ket_thuc', '>=', $startDate)
            ->whereDate('thoi_diem_ket_thuc', '<=', $endDate)
            ->whereIn('quan', $districts)
            ->where('loai_wo', 'PAKH')
            ->where('wo_qua_han', '!=', '#VALUE!')
            ->selectRaw($rawQuery)
            ->groupBy('day', 'week', 'month', 'year', 'ttkv', 'quanhuyen', 'danh_gia')
            ->get()
            ->groupBy('area_name')
            ->map(function ($area_group) use ($danh_gia_wo_thuc_hien, $init_values_district) {
                $calculate_total = function ($group, $danh_gia_wo_thuc_hien) {
                    $total_for_group = $group->groupBy('danh_gia')
                        ->mapWithKeys(function ($reason_group, $reason) use ($danh_gia_wo_thuc_hien) {
                            return [$danh_gia_wo_thuc_hien[$reason] => $reason_group->sum('eachh')];
                        });
                    $total_for_group['total'] = round(array_sum($total_for_group->toArray()), 2);

                    return $total_for_group;
                };

                $init_values_district = $init_values_district[$area_group->toArray()[0]['area_name']];
                $districts_group = $area_group->groupBy('quanhuyen')
                    ->map(fn($districts) => $calculate_total($districts, $danh_gia_wo_thuc_hien))
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
        $key_times = [
            'Trong hạn' => 'TH',
            'Quá hạn' => 'QH'
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
        // dd($filter_data);
        foreach ($key_times as $key => $value) {
            $chart_data['pie'][] = [
                'name' => $value,
                'value' => round($filter_data['HCM']['total'][$key], 2) ?? 0,
            ];
        }
        // dd($chart_data, $filter_data);
        return $chart_data;
    }
}
