<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class UserController extends Controller //implements HasMiddleware
{
    // public static function middleware(): array
    // {
    //     return [
    //         new Middleware('permission:view users', only: ['index']),
    //         new Middleware('permission:edit users', only: ['edit']),
    //         new Middleware('permission:create users', only: ['create']),
    //         new Middleware('permission:update users', only: ['update']),
    //         new Middleware('permission:destroy users', only: ['destroy']),
    //     ];
    // }

    public function index(Request $request)
    {
        // dd($request->all());
        $query = User::query();

        if ($request->has('id')) {
            $query->where('id', 'like', '%' . $request->input('id') . '%');
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('id')
            ->paginate($perPage);

        $data->getCollection()->transform(function ($user) {
            $user->role = $user->roles->first()->name ?? null;
            return $user->makeHidden('roles');
        });
        return Inertia::render('user_management/page_users', [
            'data' => UserResource::collection($data),
            'roles' => Role::get(),
        ]);
    }
    // public function index()
    // {
    //     // dd(User::get()->toArray());
    //     $users = User::orderByDesc('id')->get()->map(function ($user) {
    //         $user->role = $user->roles->first()->name ?? null;
    //         return $user->makeHidden('roles');
    //     });
    //     // dd($users);
    //     return Inertia::render('user_management/page_users', [
    //         // 'users' => Inertia::defer(fn() => UserResource::collection($users)),
    //         'users' => UserResource::collection($users),
    //         'roles' => Role::get(),
    //         //  'role' => Role::getRoleNames()
    //     ]);
    // }

    public function update_status(User $user, Request $request)
    {
        $request->validate([
            'status' => 'required|lowercase|in:block,activate'
        ]);
        $user->is_active = $request->status == 'activate';
        $user->save();

        return back();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'image' => 'sometimes|file|mimes:png,jpg,jpeg',
            'role' => 'required',
        ]);

        if ($request->has('image')) {
            $fileUrl = $request->file('image')->store('profiles', 'public');
            $validated['avatar'] = $fileUrl;
        }
        $user = User::create($validated);
        $user->syncRoles($validated['role']);

        return back();
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'required|min:8',
            'image' => 'sometimes|file|mimes:png,jpg,jpeg',
            'role' => 'required',
        ]);

        if ($request->has('image')) {
            $disk = Storage::disk('public');

            if ($user->avatar && $disk->exists($user->avatar)) {
                $disk->delete($user->avatar);
            }

            $fileUrl = $request->file('image')->store('profiles', 'public');
            $validated['avatar'] = $fileUrl;
        }

        $user->update($validated);
        $user->syncRoles($validated['role']);
        return back();
    }

    public function destroy(string $id)
    {
        $item = User::find($id);
        if ($item == null) {
            return back();
        }
        $item->update(['is_active' => false]);
        $item->delete();
        return back();
    }
}