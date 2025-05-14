<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RedmineController;
use App\Http\Controllers\UserSettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [RedmineController::class, 'dashboard'])->name('dashboard');
Route::get('/progress-rate', [RedmineController::class, 'progressRate'])->name('progress-rate');
Route::get('/individual-progress', [RedmineController::class, 'individualProgress'])->name('individual-progress');
Route::get('/api/daily-stats', [RedmineController::class, 'getDailyStats'])->name('api.daily-stats');
Route::get('/api/monthly-stats', [RedmineController::class, 'getMonthlyStats'])->name('api.monthly-stats');
Route::get('/api/progress-rate-stats', [RedmineController::class, 'getProgressRateStats'])->name('api.progress-rate-stats');
Route::get('/api/individual-progress-stats', [RedmineController::class, 'getIndividualProgressStats'])->name('api.individual-progress-stats');
Route::get('/api/user-ticket-details', [RedmineController::class, 'getUserTicketDetails'])->name('api.user-ticket-details');

Route::post('/api/user-settings', [UserSettingController::class, 'storeOrUpdate'])->name('api.user-settings.store');
Route::get('/api/user-settings', [UserSettingController::class, 'getUserSettings'])->name('api.user-settings.get');
