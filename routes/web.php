<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarqueeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\HallSlotAssignmentController;
use Illuminate\Support\Facades\Route;

// Redirect homepage to dashboard, which will prompt authentication check
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::resource('marquees', MarqueeController::class);
    Route::resource('branches', BranchController::class);
    Route::resource('users', UserController::class);
    Route::resource('staff', StaffController::class);
    
    // Halls, Slots, and Assignments
    Route::resource('halls', HallController::class);
    Route::resource('slots', SlotController::class);
    Route::get('hall-slots', [HallSlotAssignmentController::class, 'index'])->name('hall-slots.index');
});
