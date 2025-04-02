<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\RoleResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view roles', only: ['index']),
            new Middleware('permission:edit roles', only: ['edit']),
            new Middleware('permission:create roles', only: ['create']),
            new Middleware('permission:update roles', only: ['update']),
            new Middleware('permission:destroy roles', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        // dd($request->all());
        $query = Role::query();

        $permissions = Permission::orderBy('name', 'asc')->get()
            ->groupBy('framework')->map(function ($framework) {
                $framework = $framework->groupBy('name1');
                return $framework;
            })->toArray();

        if ($request->has('id')) {
            $query->where('id', 'like', '%' . $request->input('id') . '%');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->with('permissions')
            ->orderByDesc('id')
            ->paginate($perPage);

        $data->getCollection()->transform(function ($role) {
            $role->permissions = $role->permissions->pluck('name')->toArray();
            return $role;
        });
        // dd($data);
        return Inertia::render('user_management/page_roles', [
            'data' => RoleResource::collection($data),
            'permissions' => $permissions
        ]);
    }
    // public function index()
    // {
    //     $permissions = Permission::orderBy('name', 'asc')->get()
    //         ->groupBy('framework')->map(function ($framework) {
    //             $framework = $framework->groupBy('name1');
    //             return $framework;
    //         })->toArray();

    //     $roles = Role::with('permissions')
    //         ->orderBy('created_at', 'Desc')
    //         ->get()
    //         ->map(function ($role) {
    //             $role->permissions = $role->permissions->pluck('name')->toArray();
    //             return $role;
    //         });

    //     return Inertia::render('user_management/page_roles', [
    //         // 'roles' => Inertia::defer(fn() => RoleResource::collection($roles) ),
    //         'data' => RoleResource::collection($roles),
    //         // 'permissions' => inertia::defer(fn() => $permissions)
    //         'permissions' => $permissions
    //     ]);

    // }
    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|unique:roles|min:3'
            ]
        );

        $role = Role::create(['name' => $request->name, 'created_by' => auth()->user()->name]);
        if (!empty($request->permission)) {
            foreach ($request->permission as $name) {
                $role->givePermissionTo($name);
            }
        }

        return back();
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id . ',id'
        ]);

        $item = Role::findOrFail($id);

        $item->name = $request->name;
        $item->save();

        if (!empty($request->permission)) {
            $item->syncPermissions($request->permission);
        } else {
            $item->syncPermissions([]);
        }

        return back();

    }

    public function destroy(string $id)
    {

        $item = Role::find($id);
        if ($item == null) {
            return back();
        }
        $item->update(['is_active' => false]);
        $item->delete();
        return back();

    }
}
