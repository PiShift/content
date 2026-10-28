<?php

use Illuminate\Support\Facades\Route;
use PiShift\Content\Http\Controllers\Admin\AuthController;
use PiShift\Content\Http\Controllers\Admin\LocaleController;
use PiShift\Content\Http\Controllers\Admin\PageController;
use PiShift\Content\Http\Middleware\Authenticate;
use PiShift\Content\Http\Middleware\HandleAdminRequests;
use PiShift\Content\Http\Middleware\SetAdminLocale;

Route::middleware([...config('content.admin.middleware', ['web']), SetAdminLocale::class, HandleAdminRequests::class])
    ->prefix(config('content.admin.path', 'manage'))
    ->name('content.admin.')
    ->group(function (): void {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
        Route::get('locale/{locale}', LocaleController::class)->name('locale');

        Route::middleware(Authenticate::class)->group(function (): void {
            Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
            Route::redirect('/', config('content.admin.path', 'manage').'/pages')->name('home');

            Route::get('pages', [PageController::class, 'index'])->name('pages.index');
            Route::post('pages', [PageController::class, 'store'])->name('pages.store');
            Route::get('pages/{page}', [PageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
            Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
        });
    });
