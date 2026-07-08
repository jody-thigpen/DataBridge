<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\Platform\ClientController;
use App\Http\Controllers\Platform\DataSourceController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\OrganizationPackagePriceController;
use App\Http\Controllers\Platform\OrganizationSearchTypeSettingController;
use App\Http\Controllers\Platform\ReportRequestController as PlatformReportRequestController;
use App\Http\Controllers\Platform\ScreeningPackageController;
use App\Http\Controllers\Platform\SearchTypeController;
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
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{organization}', [ClientController::class, 'show'])->name('clients.show');
        Route::post('/clients/{organization}/users', [ClientController::class, 'storeUser'])->name('clients.users.store');
        Route::get('/clients/{organization}/users/{user}/edit', [ClientController::class, 'editUser'])->name('clients.users.edit');
        Route::patch('/clients/{organization}/users/{user}', [ClientController::class, 'updateUser'])->name('clients.users.update');
        Route::post('/clients/{organization}/enter', [ClientController::class, 'enter'])->name('clients.enter');
        Route::patch('/clients/{organization}/package-prices', [OrganizationPackagePriceController::class, 'update'])->name('clients.package-prices.update');
        Route::patch('/clients/{organization}/search-review-settings', [OrganizationSearchTypeSettingController::class, 'update'])->name('clients.search-review-settings.update');
        Route::patch('/clients/{organization}/client-manager', [ClientController::class, 'updateClientManager'])->name('clients.client-manager.update');

        Route::get('/report-requests', [PlatformReportRequestController::class, 'index'])->name('report-requests.index');
        Route::post('/report-requests/filters', [PlatformReportRequestController::class, 'storeFilter'])->name('report-requests.filters.store');
        Route::delete('/report-requests/filters/{savedReportRequestFilter}', [PlatformReportRequestController::class, 'destroyFilter'])->name('report-requests.filters.destroy');
        Route::get('/report-requests/{reportRequest}', [PlatformReportRequestController::class, 'show'])->name('report-requests.show');
        Route::patch('/report-requests/{reportRequest}/assign', [PlatformReportRequestController::class, 'assign'])->name('report-requests.assign');
        Route::patch('/report-requests/{reportRequest}/approve', [PlatformReportRequestController::class, 'approve'])->name('report-requests.approve');
        Route::patch('/report-requests/{reportRequest}/reject', [PlatformReportRequestController::class, 'reject'])->name('report-requests.reject');

        Route::get('/search-types', [SearchTypeController::class, 'index'])->name('search-types.index');
        Route::get('/search-types/create', [SearchTypeController::class, 'create'])->name('search-types.create');
        Route::post('/search-types', [SearchTypeController::class, 'store'])->name('search-types.store');
        Route::get('/search-types/{searchType}/edit', [SearchTypeController::class, 'edit'])->name('search-types.edit');
        Route::patch('/search-types/{searchType}', [SearchTypeController::class, 'update'])->name('search-types.update');

        Route::get('/packages', [ScreeningPackageController::class, 'index'])->name('packages.index');
        Route::get('/packages/create', [ScreeningPackageController::class, 'create'])->name('packages.create');
        Route::post('/packages', [ScreeningPackageController::class, 'store'])->name('packages.store');
        Route::get('/packages/{screeningPackage}', [ScreeningPackageController::class, 'show'])->name('packages.show');
        Route::get('/packages/{screeningPackage}/edit', [ScreeningPackageController::class, 'edit'])->name('packages.edit');
        Route::patch('/packages/{screeningPackage}', [ScreeningPackageController::class, 'update'])->name('packages.update');
        Route::post('/packages/{screeningPackage}/searches', [ScreeningPackageController::class, 'storeSearchItem'])->name('packages.searches.store');
        Route::delete('/packages/{screeningPackage}/searches/{searchType}', [ScreeningPackageController::class, 'destroySearchItem'])->name('packages.searches.destroy');

        Route::get('/data-sources', [DataSourceController::class, 'index'])->name('data-sources.index');
        Route::get('/data-sources/create', [DataSourceController::class, 'create'])->name('data-sources.create');
        Route::post('/data-sources', [DataSourceController::class, 'store'])->name('data-sources.store');
        Route::get('/data-sources/{dataSource}', [DataSourceController::class, 'show'])->name('data-sources.show');
        Route::get('/data-sources/{dataSource}/edit', [DataSourceController::class, 'edit'])->name('data-sources.edit');
        Route::patch('/data-sources/{dataSource}', [DataSourceController::class, 'update'])->name('data-sources.update');
        Route::post('/data-sources/{dataSource}/test', [DataSourceController::class, 'test'])->name('data-sources.test');

        Route::get('/users', [PlatformUserController::class, 'index'])->name('users.index');
        Route::post('/users', [PlatformUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [PlatformUserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [PlatformUserController::class, 'update'])->name('users.update');

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
        Route::get('/organization/users/{user}/edit', [OrganizationUserController::class, 'edit'])->name('organization.users.edit');
        Route::patch('/organization/users/{user}', [OrganizationUserController::class, 'update'])->name('organization.users.update');
    });
});

require __DIR__.'/auth.php';
