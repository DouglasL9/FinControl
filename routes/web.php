<?php

use App\Http\Controllers\CompanyAccessController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringPayableController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::view('/help', 'help.index')->name('help.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::post('/companies/switch', [CompanyController::class, 'switch'])->name('companies.switch');
    Route::post('/companies/{company}/archive', [CompanyController::class, 'archive'])->name('companies.archive');
    Route::get('/documentation', [DocumentationController::class, 'show'])->name('documentation.show');
    Route::get('/company', [CompanyController::class, 'show'])->name('company.show');
    Route::patch('/company/details', [CompanyController::class, 'updateDetails'])->name('company.details.update');
    Route::patch('/company/branding', [CompanyController::class, 'updateBranding'])->name('company.branding.update');
    Route::post('/company/units', [UnitController::class, 'store'])->name('company.units.store');
    Route::patch('/company/units/{unit}', [UnitController::class, 'update'])->name('company.units.update');
    Route::patch('/company/units/{unit}/toggle', [UnitController::class, 'toggle'])->name('company.units.toggle');
    Route::post('/company/suppliers', [SupplierController::class, 'store'])->name('company.suppliers.store');
    Route::patch('/company/suppliers/{supplier}', [SupplierController::class, 'update'])->name('company.suppliers.update');
    Route::patch('/company/suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('company.suppliers.toggle');
    Route::post('/company/permission-profiles', [CompanyAccessController::class, 'storeProfile'])
        ->name('company.permission-profiles.store');
    Route::patch('/company/permission-profiles/{profileId}', [CompanyAccessController::class, 'updateProfile'])
        ->name('company.permission-profiles.update');
    Route::delete('/company/permission-profiles/{profileId}', [CompanyAccessController::class, 'destroyProfile'])
        ->name('company.permission-profiles.destroy');
    Route::patch('/company/members/{userId}/permissions', [CompanyAccessController::class, 'assignProfile'])
        ->name('company.members.permissions.update');
    Route::patch('/company/members/{userId}/role', [CompanyAccessController::class, 'updateRole'])
        ->name('company.members.role.update');
    Route::get('/company/invitations/users', [CompanyInvitationController::class, 'searchUsers'])
        ->middleware('throttle:30,1')
        ->name('company-invitations.users.search');
    Route::post('/company/invitations', [CompanyInvitationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('company-invitations.store');

    Route::get('/company-invitations/{token}', [CompanyInvitationController::class, 'show'])
        ->name('company-invitations.show');
    Route::post('/company-invitations/{token}', [CompanyInvitationController::class, 'accept'])
        ->name('company-invitations.accept');

    Route::get('/payables', [PayableController::class, 'index'])->name('payables.index');
    Route::post('/payables/contracts', [RecurringPayableController::class, 'store'])->name('payables.contracts.store');
    Route::patch('/payables/contracts/{contract}/toggle', [RecurringPayableController::class, 'toggle'])
        ->name('payables.contracts.toggle');
    Route::patch('/payables/{payable}', [PayableController::class, 'update'])->name('payables.update');
    Route::patch('/payables/{payable}/payment', [PayableController::class, 'togglePayment'])
        ->name('payables.payment.toggle');
});

require __DIR__.'/auth.php';
