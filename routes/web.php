<?php

use App\Http\Controllers\FileUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FileUploadController::class, 'index'])->name('index');
Route::post('/', [FileUploadController::class, 'store'])->name('store');
Route::get('/api/status/{id}', [FileUploadController::class, 'status'])->name('status');
Route::get('/download/{id}', [FileUploadController::class, 'download'])->name('download');
