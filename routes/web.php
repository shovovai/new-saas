<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\ApiKeyController as AdminApiKeyController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BillingRecordController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationRequestController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AlertPreferenceController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PenTestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Viewable whether logged in or not — the page itself tells a guest to
// log in or register, and carries the invitation through either path.
Route::get('/invitations/{invitation}/{token}', [InvitationController::class, 'show'])->name('team.invitations.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
    Route::get('/websites/create', [WebsiteController::class, 'create'])->name('websites.create');
    Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');

    Route::middleware('team.member')->group(function () {
        Route::get('/websites/{website}', [WebsiteController::class, 'show'])->name('websites.show');
        Route::post('/websites/{website}/verify', [WebsiteController::class, 'verifyNow'])->name('websites.verify');
        Route::patch('/websites/{website}', [WebsiteController::class, 'update'])->name('websites.update');
        Route::post('/websites/{website}/pause', [WebsiteController::class, 'pause'])->name('websites.pause');
        Route::post('/websites/{website}/resume', [WebsiteController::class, 'resume'])->name('websites.resume');
        Route::delete('/websites/{website}', [WebsiteController::class, 'destroy'])->name('websites.destroy');

        Route::get('/websites/{website}/scans/{type}', [ScanController::class, 'show'])
            ->whereIn('type', ['performance', 'seo', 'security', 'accessibility'])
            ->name('scans.show');

        Route::get('/websites/{website}/monitoring', [MonitoringController::class, 'show'])->name('monitoring.show');

        Route::get('/websites/{website}/ai', [AiAssistantController::class, 'show'])->name('ai.show');

        Route::get('/websites/{website}/pentest', [PenTestController::class, 'show'])->name('pentest.show');
        Route::post('/websites/{website}/pentest/authorize', [PenTestController::class, 'authorize'])->name('pentest.authorize');

        Route::get('/websites/{website}/reports/{type}.pdf', [ReportsController::class, 'exportPdf'])
            ->whereIn('type', ['executive', 'developer', 'performance', 'seo', 'security', 'accessibility', 'pentest'])
            ->name('reports.export-pdf');

        Route::middleware('website.verified')->group(function () {
            Route::post('/websites/{website}/scans/{type}', [ScanController::class, 'store'])
                ->whereIn('type', ['performance', 'seo', 'security', 'accessibility'])
                ->name('scans.store');

            Route::post('/websites/{website}/ai/messages', [AiAssistantController::class, 'store'])->name('ai.store');

            Route::post('/websites/{website}/pentest', [PenTestController::class, 'store'])->name('pentest.store');
        });
    });

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/change-plan', [BillingController::class, 'changePlan'])->name('billing.change-plan');
    Route::post('/billing/checkout', [CheckoutController::class, 'store'])->name('billing.checkout');

    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    Route::get('/alerts', [AlertPreferenceController::class, 'index'])->name('alerts.index');
    Route::put('/alerts', [AlertPreferenceController::class, 'update'])->name('alerts.update');
    Route::put('/alerts/destinations', [AlertPreferenceController::class, 'updateDestinations'])->name('alerts.update-destinations');

    Route::get('/team', [TeamController::class, 'show'])->name('team.show');
    Route::post('/team/invitations', [TeamController::class, 'invite'])->name('team.invite');
    Route::delete('/team/invitations/{invitation}', [TeamController::class, 'revokeInvitation'])->name('team.revoke-invitation');
    Route::patch('/team/members/{user}', [TeamController::class, 'updateRole'])->name('team.update-role');
    Route::delete('/team/members/{user}', [TeamController::class, 'removeMember'])->name('team.remove-member');

    Route::post('/invitations/{invitation}/{token}/accept', [InvitationController::class, 'accept'])->name('team.invitations.accept');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export.csv', [ReportsController::class, 'exportCsv'])->name('reports.export-csv');

    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
    Route::post('/support/{supportTicket}/replies', [SupportTicketController::class, 'reply'])->name('support.reply');

    Route::prefix('admin')->name('admin.')->middleware('platform.admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
        Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy'])->name('plans.destroy');
        Route::post('/plans/{plan}/features', [AdminPlanController::class, 'toggleFeature'])->name('plans.toggle-feature');

        Route::get('/feature-flags', [FeatureFlagController::class, 'index'])->name('feature-flags.index');
        Route::patch('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'update'])->name('feature-flags.update');

        Route::get('/verification-requests', [VerificationRequestController::class, 'index'])->name('verification-requests.index');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
        Route::patch('/users/{user}/toggle-suspension', [AdminUserController::class, 'toggleSuspension'])->name('users.toggle-suspension');

        Route::get('/permissions', [AdminPermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions/toggle', [AdminPermissionController::class, 'toggle'])->name('permissions.toggle');

        Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
        Route::patch('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');

        Route::get('/invoices', [BillingRecordController::class, 'invoices'])->name('invoices.index');
        Route::get('/payments', [BillingRecordController::class, 'payments'])->name('payments.index');

        Route::get('/api-keys', [AdminApiKeyController::class, 'index'])->name('api-keys.index');
        Route::patch('/api-keys/{apiKey}/revoke', [AdminApiKeyController::class, 'revoke'])->name('api-keys.revoke');

        Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
        Route::patch('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('/support-tickets', [AdminSupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::get('/support-tickets/{supportTicket}', [AdminSupportTicketController::class, 'show'])->name('support-tickets.show');
        Route::post('/support-tickets/{supportTicket}/replies', [AdminSupportTicketController::class, 'reply'])->name('support-tickets.reply');
        Route::patch('/support-tickets/{supportTicket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('support-tickets.update-status');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/logs', [AuditLogController::class, 'index'])->name('logs.index');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Called by the payment provider, not a logged-in browser — no auth, no
// CSRF token available. Each gateway verifies its own signature/IPN
// validation before applying anything (see bootstrap/app.php CSRF except).
Route::post('/webhooks/{provider}', [CheckoutController::class, 'webhook'])
    ->whereIn('provider', ['stripe', 'paddle', 'sslcommerz'])
    ->name('billing.webhook');

require __DIR__.'/auth.php';
