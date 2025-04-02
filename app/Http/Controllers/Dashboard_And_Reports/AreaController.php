<?php

namespace App\Http\Controllers\Dashboard_And_Reports;

use App\Http\Controllers\Controller;
use App\Http\Resources\AreaResource;
use App\Models\Dashboard_And_Reports\Area;
use App\Models\Dashboard_And_Reports\District;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        // dd($request->all());
        $query = Area::query();

        if ($request->has('id')) {
            $query->where('id', 'like', '%' . $request->input('id') . '%');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->with('districts')->orderByDesc('id')->paginate($perPage);

        return Inertia::render('dashboard_and_reports/page_areas', [
            'data' => AreaResource::collection($data),
        ]);
    }
    // public function index()
    // {
    //     $areas = Area::with('districts')->where('deleted_at', null)->orderByDesc('id')->get();
    //     return Inertia::render('dashboard_and_reports/page_areas', [
    //         // 'areas' => Inertia::defer(fn() => AreaResource::collection($areas)),
    //         'areas' => AreaResource::collection($areas),
    //     ]);
    // }

    public function store(Request $request)
    {
        $validator = $request->validate(
            [
                'name' => 'required|string|max:255|unique:areas',
                'districts' => 'required|array|min:1',
                'districts.*.value' => 'required|string|max:255|distinct',
            ]
        );

        $areaModel = new Area();
        $areaModel->name = $request['name'];
        $areaModel->created_by = auth()->user()->name;
        $areaModel->save();

        $districts = array_map(function ($district) use ($areaModel) {
            return [
                'name' => $district['value'],
                'area_id' => $areaModel->id,
                'area_name' => $areaModel->name,
            ];
        }, $request->districts);

        District::insert($districts);

        return back();
    }

    public function update(Request $request, Area $area)
    {

        $validator = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id,
            'districts' => 'required|array|min:1',
            'districts.*.value' => 'required|string|max:255|distinct',
        ]);

        $areaModel = Area::findOrFail($area->id);
        if ($areaModel->name !== $request->name) {
            $areaModel->name = $request['name'];
            $areaModel->updated_by = auth()->user()->name;
            $areaModel->save();
        }

        $districts = array_map(function ($district) use ($areaModel) {
            return [
                'name' => $district['value'],
                'area_id' => $areaModel->id,
                'area_name' => $areaModel->name,
            ];
        }, $request->districts);

        District::where('area_id', $areaModel->id)
            ->whereNotIn('name', array_column($districts, 'name'))
            ->delete();
        District::insertOrIgnore($districts);
        return back();
    }

    public function destroy(string $id)
    {
        $item = Area::find($id);
        if ($item == null) {
            return back();
        }
        $item->update(['is_active' => false]);
        $item->delete();
        return back();

    }


}
