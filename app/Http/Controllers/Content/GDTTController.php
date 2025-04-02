<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\GDTTResource;
use App\Models\Content\GDTT;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GDTTController extends Controller
{
    // public function index()
    // {
    //     $data = GDTT::orderByDesc('id')->paginate(10);
    //     return Inertia::render('content/page_gdtt', [
    //         'data' => Inertia::defer(fn() => GDTTResource::collection($data)),
    //     ]);
    // }

    public function index(Request $request)
    {
        // dd($request->all());
        $query = GDTT::query();

       $filteredDataRequest = array_diff_key($request->toArray(), ["page" => "", 'per_page' => '']);

        if ($filteredDataRequest) {
            foreach ($filteredDataRequest as $key => $value) {
                $query->where($key, 'like', '%' . $value . '%');
            }
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('content/page_gdtt', [
            'data' => GDTTResource::collection($data),
        ]);
    }
}
