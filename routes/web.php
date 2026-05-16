<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('movements', StockMovementController::class)->only(['index', 'store']);
Route::get('stock-in', [StockMovementController::class, 'stockIn'])->name('stock.in');
Route::get('stock-out', [StockMovementController::class, 'stockOut'])->name('stock.out');

Route::get('reports/active-inventory', [ReportController::class, 'activeInventory'])->name('reports.active');
Route::get('reports/daily-reconciliation', [ReportController::class, 'daily'])->name('reports.daily');
Route::get('reports/monthly-performance', [ReportController::class, 'monthly'])->name('reports.monthly');
Route::get('reports/costing-summary', [ReportController::class, 'costing'])->name('reports.costing');
Route::get('reports/inventory-history', [ReportController::class, 'history'])->name('reports.history');

Route::get('settings', [SettingsController::class, 'index'])->name('settings');
Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
Route::delete('settings/reset', [SettingsController::class, 'reset'])->name('settings.reset');
