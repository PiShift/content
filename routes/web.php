<?php

use Illuminate\Support\Facades\Route;
use PiShift\Content\Http\Controllers\PageController;
use PiShift\Content\Http\Controllers\PreviewController;

Route::middleware(config('content.routes.middleware', ['web']))->group(function (): void {
    Route::get(config('content.routes.preview_prefix', 'content-preview').'/{page}', PreviewController::class)
        ->middleware('signed')
        ->name('content.preview');

    if (config('content.routes.pages', true)) {
        Route::fallback(PageController::class)->name('content.page');
    }
});
