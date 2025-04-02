<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\SCTDResource;
use App\Models\Content\SCTD;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SCTDController extends Controller
{
    //  public function index()
    // {
    //     $data = SCTD::orderByDesc('id')->get();
      
    //     return Inertia::render('content/page_sctd', [
    //         'data' => Inertia::defer(fn() => SCTDResource::collection($data)),
    //     ]);
    // }

       public function index(Request $request)
    {
        // dd($request->all());
        $query = SCTD::query();

        if ($request->has('id')) {
            $query->where('id', 'like', '%' . $request->input('id') . '%');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('content/page_sctd', [
            'data' => SCTDResource::collection($data),
        ]);
    }
}
