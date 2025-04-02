<?php

namespace App\Http\Controllers\Dashboard_And_Reports;

use App\Http\Controllers\Controller;
use App\Models\Content\CDBR;
use App\Models\Content\GDTT;
use App\Models\Content\PAKH;
use App\Models\Content\PAKH1;
use App\Models\Content\SCTD;
use App\Models\Content\WOTT;
use App\Models\Content\WOTT1;
use App\Models\Dashboard_And_Reports\Area;


use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        //         $chuoi = "TTKT Hồ Chí Minh_Đội Kỹ thuật a a a";
// $result = Str::after($chuoi, "TTKT Hồ Chí Minh_Đội Kỹ thuật ");
// dump($result);

        $data['areas'] = Area::with('districts')->get();
        return Inertia::render('dashboard_and_reports/page_dashboard', [
            'areas' => $data['areas'],
            'data' => Inertia::defer(fn() => $this->filter($request)->getData()),
            // 'data' => $this->filter($request)->getData(),
        ]);
    }

    public function filter(Request $request)
    {
        // dd($request->all());
        $value = [
            'msc' => $request->input('msc'),
            'area_ids' => $request->input('areas'),
            'district_ids' => $request->input('districts'),
            'start_date' => $request->input('startDate'),
            'end_date' => $request->input('endDate'),
        ];
        // dd($value);

        // dd($value);
        // $value['area_ids'] = ['SGN'];
        // $value['district_ids'] = ['Quận 7', 'Quận 8'];
        // $value['district_ids'] = ['Q.07' => 'Q.07','Q.08' => 'Q.08','H.Nhà Bè'=>'H.Nhà Bè'];
        // $value['district_ids'] = ['Quận 7', 'Quận 8', 'Nhà Bè', 'Quận 11'];
        // $value['district_ids'] = ['Bình Chánh', 'Tân Bình', 'Thủ Đức', 'Quận 2', 'Gò Vấp', 'Quận 1', 'Quận 10'];
        $now = now()->format('Y-m-d H:i:s');
        $mscs = [
            // 'gdtt' => GDTT::class,
            // 'sctd' => SCTD::class,
            // 'cdbr' => CDBR::class,
            'wott' => WOTT::class,
            'pakh' => PAKH::class
        ];
        // dd($value['msc']);
        foreach ($mscs as $key => $msc) {
            if ($value['msc'] == null || in_array(strtoupper($key), $value['msc'])) {
                $data[$key] = $msc::filterData($value, $now);
            }
        }

        return response()->json($data);

    }
}
