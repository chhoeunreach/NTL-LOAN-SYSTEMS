<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/loan-management/dashboard'));
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('/users/import-template', [UserController::class, 'downloadTemplate'])->name('users.import-template');
    Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
    Route::resource('/users', UserController::class)->except(['show']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::get('/roles/export', [RoleController::class, 'export'])->name('roles.export');
    Route::get('/roles/import-template', [RoleController::class, 'downloadTemplate'])->name('roles.import-template');
    Route::post('/roles/import', [RoleController::class, 'import'])->name('roles.import');
    Route::resource('/roles', RoleController::class)->except(['show']);
});
Route::redirect('/products', '/loan-management/products')->name('products.index');
Route::get('/sells/{sell}', [SellController::class, 'show'])->name('sells.show');
