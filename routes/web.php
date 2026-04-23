<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestTypeController;
use App\Http\Controllers\Staff2AdminController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

if (app()->environment('local')) {
    Route::post('/dev-login', function (Request $request) {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->input('email'))->first();
        if (!$user) {
            return back()->with('error', 'User not found');
        }

        Auth::login($user);

        return redirect()->intended('dashboard');
    })->name('dev.login');
}

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/edit', [RequestController::class, 'edit'])->name('requests.edit');
    Route::patch('/requests/{id}', [RequestController::class, 'update'])->name('requests.update');
    Route::get('/requests/{id}/print', [RequestController::class, 'printSummary'])->name('requests.print');

    Route::middleware('role:staff1,staff2')->group(function () {
        Route::patch('/requests/{id}/status', [RequestController::class, 'updateStatus'])
            ->middleware('throttle:30,1')
            ->name('requests.updateStatus');
        Route::post('/requests/{id}/comments', [RequestController::class, 'addComment'])->name('requests.comment');
    });

    Route::middleware('role:staff2')->group(function () {
        Route::post('/requests/{requestId}/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/form-templates', [FormTemplateController::class, 'store'])->name('form-templates.store');
        Route::delete('/form-templates/{id}', [FormTemplateController::class, 'destroy'])->name('form-templates.destroy');
        Route::get('/admin/request-types', [Staff2AdminController::class, 'requestTypes'])->name('admin.request-types');
        Route::post('/admin/request-types', [Staff2AdminController::class, 'storeRequestType'])->name('admin.request-types.store');
        Route::put('/admin/request-types/{id}', [Staff2AdminController::class, 'updateRequestType'])->name('admin.request-types.update');
        Route::delete('/admin/request-types/{id}', [Staff2AdminController::class, 'destroyRequestType'])->name('admin.request-types.destroy');
        Route::get('/requests/{id}/documents/{category}', [DocumentController::class, 'getByCategory'])->name('documents.by-category');
        Route::get('/checklists', [Staff2AdminController::class, 'checklists'])->name('admin.checklists');
        Route::post('/checklists', [Staff2AdminController::class, 'storeChecklistItem'])->name('admin.checklists.store');
        Route::put('/checklists/{id}', [Staff2AdminController::class, 'updateChecklistItem'])->name('admin.checklists.update');
        Route::delete('/checklists/{id}', [Staff2AdminController::class, 'destroyChecklistItem'])->name('admin.checklists.destroy');
        Route::patch('/checklists/reorder', [Staff2AdminController::class, 'reorderChecklistItems'])->name('admin.checklists.reorder');
        Route::get('/staff2/requests/export', [RequestController::class, 'exportExcel'])->name('requests.exportExcel');
        Route::patch('/admin/templates/{document}/zones', [Staff2AdminController::class, 'updateTemplateZones'])->name('admin.templates.zones');
        Route::patch('/admin/templates/{document}/field-zones', [Staff2AdminController::class, 'updateTemplateFieldZones'])->name('admin.templates.field-zones');
        Route::get('/staff2/templates/{document}/zones', [Staff2AdminController::class, 'showZoneDesigner'])->name('staff2.zones.edit');
        Route::post('/staff2/templates/{document}/zones', [Staff2AdminController::class, 'saveZones'])->name('staff2.zones.save');
        Route::get('/staff2/templates/{document}/pdf', [Staff2AdminController::class, 'servePdf'])->name('staff2.zones.pdf');
    });

    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{id}/preview', [DocumentController::class, 'preview'])->name('documents.preview');

    Route::middleware('role:staff1,staff2,admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/form-templates', [FormTemplateController::class, 'index'])->name('form-templates.index');
    });

    Route::get('/request-types/{id}/template', [RequestTypeController::class, 'getTemplate'])->name('request-types.template');
    Route::get('/api/request-types/{id}/fields', [RequestController::class, 'getDynamicFields'])->name('api.request-types.fields');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/{id}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.unread');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::delete('/notifications/cleanup', [NotificationController::class, 'cleanup'])->name('notifications.cleanup');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [Staff2AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/users', [Staff2AdminController::class, 'users'])->name('admin.users');
        Route::post('/admin/users', [Staff2AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::patch('/admin/users/{user}', [Staff2AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [Staff2AdminController::class, 'destroyUser'])->name('admin.users.destroy');
        Route::patch('/admin/users/{user}/reactivate', [Staff2AdminController::class, 'reactivateUser'])->name('admin.users.reactivate');
        Route::get('/admin/deployment-playbook', [Staff2AdminController::class, 'deploymentPlaybook'])->name('admin.deployment-playbook');
    });

    Route::middleware('role:staff1')->group(function () {
        Route::get('/requests/{requestModel}/checklist', [ChecklistController::class, 'show'])->name('requests.checklist.show');
        Route::post('/requests/{requestModel}/checklist', [ChecklistController::class, 'store'])->name('requests.checklist.store');
        Route::patch('/requests/{requestModel}/checklist/bulk', [ChecklistController::class, 'bulkUpdate'])->name('requests.checklist.bulk');
    });
});

require __DIR__ . '/auth.php';
