<?php

use App\Http\Controllers\Dashboard_And_Reports\AreaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');
// Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    $controllers = [
        // 'gdtt' => GDTTController::class,
        // 'sctd' => SCTDController::class,
        // 'cdbr' => CDBRController::class,
        // 'wott' => WOTTController::class,
        // 'pakh' => PAKHController::class,

        // 'permissions' => PermissionController::class,
        // 'roles' => RoleController::class,
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
