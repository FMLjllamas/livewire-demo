<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CountryOfBirthController;


Route::get('/', function() {
    return redirect()->route('birthpage.index');
    //return view('welcome');
});




Route::get('/birth', [CountryOfBirthController::class, 'index'])->name('birthpage.index');
Route::get('/birth/get', [CountryOfBirthController::class, 'show'])->name('birthpage.show');

Route::post('/birth', [CountryOfBirthController::class, 'store'])->name('birthpage.store');

Route::put('/birth/update', [CountryOfBirthController::class, 'update'])->name('birthpage.update');
Route::delete('/birth/delete', [CountryOfBirthController::class, 'destroy'])->name('birthpage.delete');

/*
Route::get('/birth/excel', [CountryOfBirthController::class, 'export']);
*/
?>