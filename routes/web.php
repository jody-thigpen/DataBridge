<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\Platform\ClientController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\UserController as PlatformUserController;
use App\Http\Controllers\Organization\BillingController;
use App\Http\Controllers\Organization\ProfileController as OrganizationProfileController;
use App\Http\Controllers\Organization\UserController as OrganizationUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/organization/switch', [OrganizationSwitchController::class, 'update'])->name('organization.switch');
    Route::delete('/organization/exit', [OrganizationSwitchController::class, 'destroy'])->name('organization.exit');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('platform')->prefix('platform')->name('platform.')->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/{organization}', [ClientController::class, 'show'])->name('clients.show');
        Route::post('/clients/{organization}/enter', [ClientController::class, 'enter'])->name('clients.enter');

        Route::get('/users', [PlatformUserController::class, 'index'])->name('users.index');
        Route::post('/users', [PlatformUserController::class, 'store'])->name('users.store');

        Route::post('/impersonation/{user}', [ImpersonationController::class, 'store'])->name('impersonation.store');
    });

    Route::delete('/impersonation', [ImpersonationController::class, 'destroy'])->name('platform.impersonation.destroy');

    Route::middleware('org.access')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/requests/create', [ReportRequestController::class, 'create'])->name('reports.requests.create');
        Route::post('/reports/requests', [ReportRequestController::class, 'store'])->name('reports.requests.store');

        Route::get('/organization/profile', [OrganizationProfileController::class, 'edit'])->name('organization.profile.edit');
        Route::patch('/organization/profile', [OrganizationProfileController::class, 'update'])->name('organization.profile.update');

        Route::get('/organization/billing', [BillingController::class, 'edit'])->name('organization.billing.edit');

        Route::get('/organization/users', [OrganizationUserController::class, 'index'])->name('organization.users.index');
        Route::post('/organization/users', [OrganizationUserController::class, 'store'])->name('organization.users.store');
    });
});

require __DIR__.'/auth.php';
