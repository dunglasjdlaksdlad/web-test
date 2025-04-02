<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Str;

class PermissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view permissions', only: ['index']),
            new Middleware('permission:edit permissions', only: ['edit']),
            new Middleware('permission:create permissions', only: ['create']),
            new Middleware('permission:update permissions', only: ['update']),
            new Middleware('permission:destroy permissions', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        // dd($request->all());
        $query = Permission::query();

        if ($request->has('id')) {
            $query->where('id', 'like', '%' . $request->input('id') . '%');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('user_management/page_permissions', [
            'data' => PermissionResource::collection($data),
        ]);
    }
    // public function index()
    // {
    //     $permission = Permission::orderBy('id', 'desc')->get();
    //     return Inertia::render('user_management/page_permissions', [
    //         // 'permissions' => Inertia::defer(fn() => PermissionResource::collection($permission) ),
    //         'permissions' => PermissionResource::collection($permission),
    //     ]);

    // }
    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|array|min:1',
                'name.*.value' => 'required|string|max:255|unique:permissions,name|distinct',
                'framework' => 'required|string|max:255',
            ]
        );

        $framework = $request->framework;
        $permissions = array_map(function ($data) use ($framework) {
            return [
                'name1' => Str::after($data['value'], ' '),
                'name' => $data['value'],
                'framework' => $framework,
                'created_by' => auth()->user()->name
            ];
        }, $request->name);
        foreach ($permissions as $value) {
            Permission::create($value);
        }

        return back();
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $id . ',id'
        ]);

        $item = Permission::findOrFail($id);

        $item->name = $request->name;
        $item->save();
        return back();
    }
    public function destroy(Request $request)
    {
        $id = $request->id;

        $item = Permission::find($id);
        if ($item == null) {
            return back();
        }
        $item->update(['is_active' => false]);
        $item->delete();
        return back();
    }
}
