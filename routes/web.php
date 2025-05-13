<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RedmineController;

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
Route::get('/individual-consumption', [RedmineController::class, 'individualConsumption'])->name('individual-consumption');
Route::get('/api/daily-stats', [RedmineController::class, 'getDailyStats'])->name('api.daily-stats');
Route::get('/api/monthly-stats', [RedmineController::class, 'getMonthlyStats'])->name('api.monthly-stats');
Route::get('/api/progress-rate-stats', [RedmineController::class, 'getProgressRateStats'])->name('api.progress-rate-stats');
Route::get('/api/individual-consumption-stats', [RedmineController::class, 'getIndividualConsumptionStats'])->name('api.individual-consumption-stats');
