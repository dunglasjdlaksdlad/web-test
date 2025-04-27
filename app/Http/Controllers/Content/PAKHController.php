<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\PAKHResource;
use App\Models\Content\PAKH;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PAKHController extends Controller
{
    public function index(Request $request)
    {
        $query = PAKH::pageBuildMainQuery();
        $filters = [
            'ttkv' => $request->input('filters.ttkv'),
            'quan' => $request->input('filters.quan'),
            'time_status' => $request->input('filters.headerValue'),
            'start_date' => $request->input('filters.start_date'),
            'end_date' => $request->input('filters.end_date'),
        ];
        // dd($filters);
        $query->when(!empty($filters['ttkv']) || !empty($filters['quan']), function ($q) use ($filters) {
            $q->where(function ($subQuery) use ($filters) {
                if (!empty($filters['ttkv'])) {
                    $subQuery->whereIn('sub.ttkv', $filters['ttkv']);
                }
                if (!empty($filters['quan'])) {
                    $subQuery->WhereIn('sub.quan', $filters['quan']);
                }
            });
        });
        $query->when($filters['time_status'], function ($q) use ($filters) {
            $q->where('time_status', $filters['time_status']);
        });
        //  dd($query->get()->groupBy('time_status')->toArray());
        $filteredDataRequest = array_diff_key($request->toArray(), ['page' => '', 'per_page' => '', 'bar' => '']);

        if ($filteredDataRequest) {
            foreach ($filteredDataRequest['filters'] as $key => $value) {
                if (!is_array($value)) {
                    $query->where($key, 'like', '%' . $value . '%');
                }
            }
        }

        $perPage = $request->input('per_page', 10);
        $paginatedData = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('content/page_pakh', [
            'data' => PAKHResource::collection($paginatedData),
            'filters' => $filters,
        ]);
    }
}
