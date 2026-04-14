<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Staff2AdminController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\RequestTypeController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\OverrideController;
use App\Http\Controllers\Staff2WorkflowController;

// ─── Welcome ─────────────────────────────────────────────────────────────────
Route::get('/', fn() => view('welcome'));

// ─── Dev quick-switch (local only) ───────────────────────────────────────────
if (app()->environment('local')) {
    Route::post('/dev-login', function (Request $request) {
        $request->validate(['email' => ['required', 'email']]);
        
        $email = $request->input('email');
        
        // Find user by email
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            return back()->with('error', 'User not found');
        }
        
        // Log in as user
        Auth::login($user);
        
        return redirect()->intended('dashboard');
    })->name('dev.login');
}

// ─── Dashboard ───────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Requests ───────────────────────────────────────────────────────────────
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/edit', [RequestController::class, 'edit'])->name('requests.edit');
    Route::patch('/requests/{id}', [RequestController::class, 'update'])->name('requests.update');
    Route::get('/requests/{id}/pdf/inline', [RequestController::class, 'viewGeneratedPdf'])
    ->name('requests.pdf.inline');

    // ── Staff 1 + 2 + Dean ──────────────────────────────────────────────────────────
    Route::middleware('role:staff1,staff2,dean')->group(function () {
        Route::patch('/requests/{id}/status', [RequestController::class, 'updateStatus'])->name('requests.updateStatus');
        Route::patch('/requests/{id}/priority', [RequestController::class, 'updatePriority'])->name('requests.updatePriority');
        Route::post('/requests/{id}/comments', [RequestController::class, 'addComment'])->name('requests.comment');
    });

    Route::middleware('role:staff1,staff2,dean,admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/form-templates', [FormTemplateController::class, 'index'])->name('form-templates.index');
    });

    // ── All roles — view requests ─────────────────────────────────────────────
    Route::get('/requests/{id}/print', [RequestController::class, 'printSummary'])->name('requests.print');
    Route::get('/requests/{id}/pdf', [RequestController::class, 'downloadPdf'])->name('requests.pdf');
    Route::get('/requests/{id}/pdf/view', [RequestController::class, 'viewGeneratedPdf'])->name('requests.pdf.view');
    // Backward-compatible alias for older view references.
    Route::get('/requests/{id}/download-pdf', [RequestController::class, 'downloadPdf'])->name('requests.downloadPdf');
    Route::get('/requests/{id}/document', [RequestController::class, 'showMainDocument'])->name('requests.document.main');
    Route::get('/requests/{id}/documents/additional/{index}', [RequestController::class, 'showAdditionalDocument'])->name('requests.document.additional');
    
    // ── Template Preview Route ─────────────────────────────────────────────
    Route::get('/request-types/{id}/template', [RequestTypeController::class, 'getTemplate'])->name('request-types.template');

    // ── API Routes for Dynamic Form Fields ─────────────────────────────────
    Route::get('/api/request-types/{id}/fields', [RequestController::class, 'getDynamicFields'])->name('api.request-types.fields');

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/{id}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.unread');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::delete('/notifications/cleanup', [NotificationController::class, 'cleanup'])->name('notifications.cleanup');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    // ── Dean Routes ──────────────────────────────────────────────────────────────────
    Route::middleware('role:dean')->group(function () {
        Route::get('/dean/requests/{id}', [DeanController::class, 'show'])->name('dean.requests.show');
        Route::post('/dean/requests/{id}/approve', [DeanController::class, 'approve'])->name('dean.requests.approve');
        Route::post('/dean/requests/{id}/reject', [DeanController::class, 'reject'])->name('dean.requests.reject');
        Route::post('/dean/requests/{id}/return', [DeanController::class, 'returnRequest'])->name('dean.requests.return');
    });

    // ── Dean Routes (feature-flagged) ─────────────────────────────────────────
    if (config('system.features.dean_interface', false)) {
        Route::middleware('role:dean')->group(function () {
            Route::get('/dean/dashboard', [DeanController::class, 'dashboard'])->name('dean.dashboard');
        });
    }

    // ── Admin Panel (Admin role only)
    Route::middleware('role:admin')->group(function () {
        // Admin panel
        Route::get('/admin/dashboard', [Staff2AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/users', [Staff2AdminController::class, 'users'])->name('admin.users');
        Route::post('/admin/users', [Staff2AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::patch('/admin/users/{user}', [Staff2AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [Staff2AdminController::class, 'destroyUser'])->name('admin.users.destroy');
        Route::patch('/admin/users/{user}/reactivate', [Staff2AdminController::class, 'reactivateUser'])->name('admin.users.reactivate');
        Route::get('/admin/request-types', [Staff2AdminController::class, 'requestTypes'])->name('admin.request-types');
        Route::get('/admin/deployment-playbook', [Staff2AdminController::class, 'deploymentPlaybook'])->name('admin.deployment-playbook');
        Route::post('/admin/request-types', [Staff2AdminController::class, 'storeRequestType'])->name('admin.request-types.store');
        Route::put('/admin/request-types/{id}', [Staff2AdminController::class, 'updateRequestType'])->name('admin.request-types.update');
        Route::delete('/admin/request-types/{id}', [Staff2AdminController::class, 'destroyRequestType'])->name('admin.request-types.destroy');

        // Form templates
        Route::post('/form-templates', [FormTemplateController::class, 'store'])->name('form-templates.store');
        Route::delete('/form-templates/{id}', [FormTemplateController::class, 'destroy'])->name('form-templates.destroy');
    });

    // Staff 2 Routes (workflow only)
    Route::middleware('role:staff2')->group(function () {
        // Override functionality
        Route::post('/requests/toggle-override-mode', [RequestController::class, 'toggleOverrideMode'])
            ->name('requests.toggleOverrideMode');

        Route::get('/workflow-settings', [Staff2WorkflowController::class, 'index'])
            ->name('staff2.workflow.index');
        Route::patch('/workflow-settings/{requestType}', [Staff2WorkflowController::class, 'update'])
            ->name('staff2.workflow.update');
    });

    // Staff 2-only tools
    Route::middleware('role:staff2')->group(function () {
        Route::get('/staff2/requests/export', [RequestController::class, 'exportExcel'])->name('requests.exportExcel');
    });
});

require __DIR__ . '/auth.php';
