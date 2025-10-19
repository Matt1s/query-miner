<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

// Query Miner search page
Route::get('/', function () {
    return view('search');
});

// API endpoint used by the frontend to perform search via Google Custom Search
Route::post('/search/api', [SearchController::class, 'search']);