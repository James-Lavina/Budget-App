<?php

use App\Http\Livewire\Admin\Dashboard as adminDashboard;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\Register;
use App\Http\Livewire\Student\Dashboard as studentDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Guest Routes
Route::middleware(['guest'])->group(function() {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
    Route::get('/', function() {
        return redirect()->route('login');
    });
});

// Authenticated Routes
Route::middleware(['auth'])->group(function() {
    // Student Routes
    Route::get('/dashboard', studentDashboard::class)->name('student.dashboard');
    // Admin Routes
    Route::middleware(['admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function() {
            Route::get('/dashboard', adminDashboard::class)->name('dashboard');
    });
    // Global Routes
    Route::get('/logout', function() {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});