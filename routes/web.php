<?php

use Illuminate\Support\Facades\Route;

// Query Miner search page
Route::get('/', function () {
    return view('search');
});