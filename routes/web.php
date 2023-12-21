<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    if (auth()->user())
    {
        return redirect('/admin');
    }

    return redirect()->to('/admin/login');
});

\Livewire\Livewire::setScriptRoute(function ($handle) {
    return Route::get('/aldiwan/livewire/livewire.js', $handle);
});

\Livewire\Livewire::setUpdateRoute(function ($handle) {
    return Route::post('/aldiwan/livewire/update', $handle);
});
