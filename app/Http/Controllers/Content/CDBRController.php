<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\CDBRResource;
use App\Models\Content\CDBR;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CDBRController extends Controller
{
    // public function index()
    // {
    //     $data = CDBR::orderByDesc('id')->get();
    //     return Inertia::render('content/page_cdbr', [
    //         'data' => Inertia::defer(fn() => CDBRResource::collection($data)),
    //     ]);
    // }

      public function index(Request $request)
    {
        // dd($request->all());
        $query = CDBR::query();

        if ($request->has('id')) {
            $query->where('id', 'like', '%' . $request->input('id') . '%');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('content/page_cdbr', [
            'data' => CDBRResource::collection($data),
        ]);
    }
}
