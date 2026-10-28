<?php

use Illuminate\Support\Facades\Route;
use PiShift\Content\Http\Controllers\Admin\AuthController;
use PiShift\Content\Http\Controllers\Admin\CollectionController;
use PiShift\Content\Http\Controllers\Admin\GlobalController;
use PiShift\Content\Http\Controllers\Admin\LocaleController;
use PiShift\Content\Http\Controllers\Admin\MediaController;
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
            Route::post('pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
            Route::get('pages/{page}/revisions', [PageController::class, 'revisions'])->name('pages.revisions');
            Route::post('pages/{page}/revisions/{revision}/restore', [PageController::class, 'restore'])->name('pages.restore');

            Route::get('media', [MediaController::class, 'index'])->name('media.index');
            Route::get('media/list', [MediaController::class, 'list'])->name('media.list');
            Route::post('media', [MediaController::class, 'store'])->name('media.store');
            Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
            Route::put('media/{media}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

            Route::get('collections/{collection}', [CollectionController::class, 'index'])->name('collections.index');
            Route::get('collections/{collection}/create', [CollectionController::class, 'create'])->name('collections.create');
            Route::post('collections/{collection}', [CollectionController::class, 'store'])->name('collections.store');
            Route::get('collections/{collection}/{record}', [CollectionController::class, 'edit'])->name('collections.edit');
            Route::put('collections/{collection}/{record}', [CollectionController::class, 'update'])->name('collections.update');
            Route::delete('collections/{collection}/{record}', [CollectionController::class, 'destroy'])->name('collections.destroy');

            Route::get('globals', [GlobalController::class, 'index'])->name('globals.index');
            Route::get('globals/{key}', [GlobalController::class, 'edit'])->name('globals.edit');
            Route::put('globals/{key}', [GlobalController::class, 'update'])->name('globals.update');
        });
    });
