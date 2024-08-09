<?php
use Illuminate\Http\Request;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('brainframe');
})->where('any', '.*');

Route::get('/', function () {
    return view('brainframe');
})->name('brainframe');
