<?php

use App\Http\Controllers\Content\CDBRController;
use App\Http\Controllers\Content\GDTTController;
use App\Http\Controllers\Content\PAKHController;
use App\Http\Controllers\Content\SCTDController;
use App\Http\Controllers\Content\WOTTController;
use App\Http\Controllers\Dashboard_And_Reports\AreaController;
use App\Http\Controllers\Dashboard_And_Reports\DashboardController;
use App\Http\Controllers\Dashboard_And_Reports\FileManagerController;
use App\Http\Controllers\User_Management\PermissionController;
use App\Http\Controllers\User_Management\RoleController;
use App\Http\Controllers\User_Management\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');
// Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::get('dashboard', function () {
    //     return Inertia::render('dashboard');
    // })->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/filter', [DashboardController::class, 'filter'])->name('dashboard.filter');

    Route::controller(UserController::class)->group(function () {
        Route::match(['get', 'post'], '/users', 'index')->name('users.index');
        Route::resource('/users', UserController::class)->except(['index', 'update']);
        // Route::resource('/users', UserController::class)->except("update");
        Route::post("/users/{user}", [UserController::class, 'update_status'])->name("users.status");
        Route::post("/users/{user}/update", [UserController::class, 'update'])->name("users.update");
    });

    Route::controller(FileManagerController::class)->group(function () {
        Route::match(['get', 'post'], '/filemanager', 'index')->name('filemanager.index');
        Route::post('/filemanager/store', 'store')->name('filemanager.store');
        Route::resource('/filemanager', FileManagerController::class)->except(['index', 'update', 'store', 'create']);
    });

    $controllers = [
        'gdtt' => GDTTController::class,
        'sctd' => SCTDController::class,
        'cdbr' => CDBRController::class,
        'wott' => WOTTController::class,
        'pakh' => PAKHController::class,

        'permissions' => PermissionController::class,
        'roles' => RoleController::class,
        'areas' => AreaController::class,
    ];

    foreach ($controllers as $prefix => $controller) {
        Route::controller($controller)->group(function () use ($prefix, $controller) {
            Route::match(['get', 'post'], "/$prefix", 'index')->name("$prefix.index");
            Route::resource("/$prefix", $controller)->except('index');
        });
    }
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
