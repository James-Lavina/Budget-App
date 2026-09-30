<?php

use App\Http\Livewire\Admin\ActivityLogIndex;
use App\Http\Livewire\Admin\Dashboard as adminDashboard;
use App\Http\Livewire\Admin\ExpenseCategories;
use App\Http\Livewire\Admin\OcrAiSettings;
use App\Http\Livewire\Admin\Reports;
use App\Http\Livewire\Admin\RiskRules;
use App\Http\Livewire\Admin\UserManagement;
use App\Http\Livewire\Admin\Settings as adminSettings;
use App\Http\Livewire\Admin\AdminManagement;
use App\Http\Livewire\Auth\ForgotPassword;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\Register;
use App\Http\Livewire\Auth\ResetPassword;
use App\Http\Livewire\Student\AddBudgetFunds;
use App\Http\Livewire\Student\AllExpenses;
use App\Http\Livewire\Student\BudgetSetup;
use App\Http\Livewire\Student\Dashboard as studentDashboard;
use App\Http\Livewire\Student\EditExpense;
use App\Http\Livewire\Student\GoalsManager;
use App\Http\Livewire\Student\LogExpense;
use App\Http\Livewire\Student\NotificationIndex;
use App\Http\Livewire\Student\Profile;
use App\Http\Livewire\Student\ScanExpense;
use App\Http\Livewire\Student\Settings;
use App\Http\Livewire\Student\SpendingForecast;
use App\Http\Livewire\Student\WhatIfSimulator;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest Routes
Route::middleware(['guest'])->group(function() {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
    // NEW: password reset flow — previously the "Forgot password?" link
    // on the login page pointed at a route that never existed.
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::get('/', function() {
        return view('welcome');
    });
});

// Authenticated Routes
Route::middleware(['auth', 'maintenance.check'])->group(function() {
    // Student Routes
    Route::name('student.')
        ->group(function() {
            Route::get('/dashboard', studentDashboard::class)->name('dashboard');
            Route::get('/budget-setup', BudgetSetup::class)->name('budget-setup');
            Route::get('/expenses', AllExpenses::class)->name('expenses.index');
            Route::get('/expenses/create', LogExpense::class)->name('expenses.create');
            Route::get('/expenses/receipt-scanner', ScanExpense::class)->name('receipt-scanner');
            Route::get('/expenses/{id}/edit', EditExpense::class)->name('expenses.edit');
            Route::get('/budget/add-funds', AddBudgetFunds::class)->name('budget.add');
            Route::get('/forecast', SpendingForecast::class)->name('forecast');
            Route::get('/savings-goals', GoalsManager::class)->name('goals');
            Route::get('/simulation', WhatIfSimulator::class)->name('simulation');
            Route::get('/notification', NotificationIndex::class)->name('notifications');
            Route::get('/settings', Settings::class)->name('settings');
            Route::get('/profile', Profile::class)->name('profile');
        });

    if (app()->environment('local')) {
        Route::middleware(['auth'])
            ->prefix('test')
            ->name('test.')
            ->group(function () {
                Route::get('/fast-forward', function (\Illuminate\Http\Request $request) {
                    $date = $request->query('date');

                    if ($date) {
                        \Illuminate\Support\Carbon::setTestNow($date);
                        session(['test_fake_now' => $date]);
                    }

                    return redirect()->back();
                })->name('fast-forward');
                
                Route::get('/fast-forward/reset', function () {
                    \Illuminate\Support\Carbon::setTestNow(null);
                    session()->forget('test_fake_now');

                    return redirect()->back();
                })->name('fast-forward.reset');
            });
    }

    // Admin Routes
    Route::middleware(['admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function() {
            Route::get('/dashboard', adminDashboard::class)->name('dashboard');
            Route::get('/users', UserManagement::class)->name('users');
            Route::get('/categories', ExpenseCategories::class)->name('categories');
            Route::get('/risk-rules', RiskRules::class)->name('risk-rules');
            Route::get('/ocr-ai-settings', OcrAiSettings::class)->name('ocr-ai-settings');
            Route::get('/reports', Reports::class)->name('reports');
            Route::get('/activity-logs', ActivityLogIndex::class)->name('activity-logs');
            Route::get('/settings', adminSettings::class)->name('settings');
            Route::middleware(['super_admin'])->group(function () {
                Route::get('/admin-accounts', AdminManagement::class)->name('accounts');
            });
    });

    // Global Routes
    Route::post('/', function() {
        if (auth()->check()) {
            \App\Models\ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'auth_logout',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Logged out',
            ]);
        }

        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return view('welcome');
    })->name('logout');
});