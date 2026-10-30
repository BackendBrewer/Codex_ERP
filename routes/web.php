<?php

use App\Http\Controllers\AcademyModuleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdmin\AcademyController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
});

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isSuperAdmin()
            ? redirect()->route('superadmin.academies')
            : redirect()->route('dashboard');
    }

    return view('welcome', ['recaptchaSiteKey' => config('services.recaptcha.site_key')]);
})->name('home');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:super_admin')->prefix('superadmin')->name('superadmin.')->group(function (): void {
        Route::get('/academies', [AcademyController::class, 'index'])->name('academies');
        Route::get('/academies/create', [AcademyController::class, 'create'])->name('academies.create');
        Route::post('/academies', [AcademyController::class, 'store'])->name('academies.store');
        Route::get('/academies/{academy}/edit', [AcademyController::class, 'edit'])->name('academies.edit');
        Route::put('/academies/{academy}', [AcademyController::class, 'update'])->name('academies.update');
        Route::patch('/academies/{academy}/status', [AcademyController::class, 'toggleStatus'])->name('academies.status');
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/reports', [AcademyModuleController::class, 'reports'])->name('reports');
        Route::get('/fees/bulk', [AcademyModuleController::class, 'bulkFeeForm'])->name('fees.bulk.form');
        Route::post('/fees/bulk', [AcademyModuleController::class, 'generateBulkFees'])->name('fees.bulk.generate');
        Route::get('/fees/monthly-students', [AcademyModuleController::class, 'monthlyFeeStudents'])->name('fees.monthly-students');
        Route::post('/inquiries/{id}/admit', [AcademyModuleController::class, 'admitInquiry'])->whereNumber('id')->name('inquiries.admit');
        Route::get('/receipts/{id}/print', [AcademyModuleController::class, 'printReceipt'])->whereNumber('id')->name('receipts.print');
        Route::get('/fee-challans/{id}/print', [AcademyModuleController::class, 'printChallan'])->whereNumber('id')->name('challans.print');
        Route::post('/fee-challans/{id}/adjust', [AcademyModuleController::class, 'adjustFeeBill'])->whereNumber('id')->name('challans.adjust');
        Route::get('/students/{id}/workspace', [AcademyModuleController::class, 'studentWorkspace'])->whereNumber('id')->name('students.workspace');
        Route::get('/students/{id}/card', [AcademyModuleController::class, 'printStudentCard'])->whereNumber('id')->name('students.card');
        Route::get('/{module}/print', [AcademyModuleController::class, 'index'])->whereIn('module', array_keys(config('academy.modules')))->name('modules.print');
        Route::get('/fee-heads/{scope}', [AcademyModuleController::class, 'feeHeads'])->whereIn('scope', ['class', 'academy', 'student'])->name('fee-heads.index');
        Route::post('/fee-heads/{scope}', [AcademyModuleController::class, 'storeFeeHead'])->whereIn('scope', ['class', 'academy', 'student'])->name('fee-heads.store');
        Route::put('/fee-heads/{scope}/{id}', [AcademyModuleController::class, 'updateFeeHead'])->whereIn('scope', ['class', 'academy', 'student'])->whereNumber('id')->name('fee-heads.update');
        Route::delete('/fee-heads/{scope}/{id}', [AcademyModuleController::class, 'deleteFeeHead'])->whereIn('scope', ['class', 'academy', 'student'])->whereNumber('id')->name('fee-heads.delete');
        Route::get('/{module}', [AcademyModuleController::class, 'index'])->whereIn('module', array_keys(config('academy.modules')))->name('modules.index');
        Route::get('/{module}/create', [AcademyModuleController::class, 'create'])->whereIn('module', array_keys(config('academy.modules')))->name('modules.create');
        Route::post('/{module}', [AcademyModuleController::class, 'store'])->whereIn('module', array_keys(config('academy.modules')))->name('modules.store');
        Route::get('/{module}/{id}/edit', [AcademyModuleController::class, 'edit'])->whereIn('module', array_keys(config('academy.modules')))->whereNumber('id')->name('modules.edit');
        Route::put('/{module}/{id}', [AcademyModuleController::class, 'update'])->whereIn('module', array_keys(config('academy.modules')))->whereNumber('id')->name('modules.update');
        Route::delete('/{module}/{id}', [AcademyModuleController::class, 'destroy'])->whereIn('module', array_keys(config('academy.modules')))->whereNumber('id')->name('modules.destroy');
    });
});
