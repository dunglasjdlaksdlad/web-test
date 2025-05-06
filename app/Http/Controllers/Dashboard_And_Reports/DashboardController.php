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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // $start = Carbon::parse('2024-01-30 04:13:57');
        // $end = Carbon::parse('2024-01-30 05:36:55');
        // $totalHours = 1.38;

        // $result = self::splitDayNightHours($start, $totalHours);

        // dd($result);


        $data['areas'] = Area::with('districts')->get();
        return Inertia::render('dashboard_and_reports/page_dashboard', [
            'areas' => $data['areas'],
            'data' => Inertia::defer(fn() => $this->filter($request)->getData()),
        ]);
    }

    // function splitDayNightHours(Carbon $start, float $totalHours): array
    // {
    //     if ($totalHours <= 0) {
    //         return [
    //             'total_hours' => 0.0,
    //             'day_hours' => 0.0,
    //             'night_hours' => 0.0,
    //         ];
    //     }

    //     $end = $start->copy()->addSeconds($totalHours * 3600);

    //     $dayHours = 0.0;
    //     $nightHours = 0.0;

    //     if ($start->copy()->addSeconds($totalHours * 3600)->isSameDay($start)) {
    //         $current = $start->copy();

    //         while ($current < $end) {
    //             $hour = (int) $current->format('H');
    //             if ($hour < 5) {
    //                 $next = $current->copy()->startOfDay()->addHours(5);
    //             } elseif ($hour >= 5 && $hour < 24) {
    //                 $next = $current->copy()->startOfDay()->addDay();
    //             }

    //             if ($next > $end) {
    //                 $next = $end;
    //             }

    //             $duration = $next->diffInSeconds($current) / 3600;

    //             if ($hour >= 5 && $hour < 24) {
    //                 $dayHours += $duration;
    //             } else {
    //                 $nightHours += $duration;
    //             }

    //             $current = $next;
    //         }
    //     } else {
    //         $current = $start->copy();
    //         while ($current < $end) {
    //             $dayEnd = $current->copy()->endOfDay();
    //             if ($dayEnd > $end) {
    //                 $dayEnd = $end;
    //             }

    //             $remainingDayHours = $dayEnd->diffInSeconds($current) / 3600;

    //             $hour = (int) $current->format('H');
    //             if ($hour < 5) {
    //                 $to5am = min($remainingDayHours, (5 - $hour));
    //                 $nightHours += $to5am;
    //                 $dayHours += max(0, $remainingDayHours - $to5am);
    //             } elseif ($hour >= 5 && $hour < 24) {
    //                 $dayHours += $remainingDayHours;
    //             }

    //             $current = $dayEnd->copy()->addSecond();
    //         }
    //     }

    //     $rawTotal = $dayHours + $nightHours;
    //     if ($rawTotal == 0) {
    //         return [
    //             'total_hours' => round($totalHours, 2),
    //             'day_hours' => 0.0,
    //             'night_hours' => 0.0,
    //         ];
    //     }

    //     $dayPercent = $dayHours / $rawTotal;
    //     $nightPercent = $nightHours / $rawTotal;

    //     $dayHoursResult = $totalHours * $dayPercent;
    //     $nightHoursResult = $totalHours * $nightPercent;

    //     $dayHoursResult = number_format($dayHoursResult, 2, '.', '');
    //     $nightHoursResult = number_format($nightHoursResult, 2, '.', '');

    //     $dayHoursResult = (float) $dayHoursResult;
    //     $nightHoursResult = (float) $nightHoursResult;

    //     return [
    //         'total_hours' => round($totalHours, 2),
    //         'day_hours' => $dayHoursResult,
    //         'night_hours' => $nightHoursResult,
    //     ];
    // }




    public function filter(Request $request)
    {
        $value = [
            'msc' => $request->input('msc'),
            'ttkv' => $request->input('areas'),
            'quan' => $request->input('districts'),
            'time' => $request->input('time'),
        ];

        // $value['ttkv'] = ['PTO'];
        // $value['quan'] = ['Quận 11','Quận Tân Bình','Quận Tân Phú'];
        $now = now()->format('Y-m-d H:i:s');
        $mscs = [
            'gdtt' => GDTT::class,
            // 'sctd' => SCTD::class,
            // 'cdbr' => CDBR::class,
            'wott' => WOTT::class,
            'pakh' => PAKH::class
        ];
        foreach ($mscs as $key => $msc) {
            if ($value['msc'] == null || in_array(strtoupper($key), $value['msc'])) {
                $data[$key] = $msc::filterData($value, $now);
            }
        }

        return response()->json($data);

    }

}
