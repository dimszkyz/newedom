<?php

use App\Http\Controllers\EdomPublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EdomPublicController::class, 'index'])->name('edom.home');
Route::post('/', [EdomPublicController::class, 'submitFromHome'])->name('edom.home.submit');

Route::get('/edom/{edom}/isi', [EdomPublicController::class, 'show'])->name('edom.fill');
Route::post('/edom/{edom}/isi', [EdomPublicController::class, 'submit'])->name('edom.submit');
