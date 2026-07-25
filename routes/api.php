<?php

use App\Http\Controllers\Api\AlertApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\ScanApiController;
use App\Http\Controllers\Api\WebsiteApiController;
use Illuminate\Support\Facades\Route;

/**
 * Public REST API (Functional Spec §14): team-level API keys, gated by
 * the api.access plan feature. Authentication and the feature check both
 * happen in AuthenticateApiKey, ahead of the throttle so limits are keyed
 * per API key rather than per IP.
 */
Route::prefix('v1')->middleware(['api.key', 'throttle:api'])->group(function () {
    Route::get('/websites', [WebsiteApiController::class, 'index'])->name('api.websites.index');
    Route::get('/websites/{website}', [WebsiteApiController::class, 'show'])->name('api.websites.show');

    Route::get('/websites/{website}/scans/{type}', [ScanApiController::class, 'show'])
        ->whereIn('type', ['performance', 'seo', 'security', 'accessibility'])
        ->name('api.scans.show');
    Route::post('/websites/{website}/scans/{type}', [ScanApiController::class, 'store'])
        ->whereIn('type', ['performance', 'seo', 'security', 'accessibility'])
        ->name('api.scans.store');

    Route::get('/reports', [ReportApiController::class, 'index'])->name('api.reports.index');
    Route::get('/websites/{website}/reports/{type}.pdf', [ReportApiController::class, 'exportPdf'])
        ->whereIn('type', ['executive', 'developer', 'performance', 'seo', 'security', 'accessibility', 'pentest'])
        ->name('api.reports.export-pdf');

    Route::get('/alerts', [AlertApiController::class, 'index'])->name('api.alerts.index');
    Route::put('/alerts', [AlertApiController::class, 'update'])->name('api.alerts.update');
});
