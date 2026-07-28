<?php

use Illuminate\Support\Facades\Route;
use MisterChameleon\Statamic\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Control Panel routes (addon-owned)
|--------------------------------------------------------------------------
|
| Registered inside Statamic's CP route group (auth + "cp" prefix, name
| prefix "statamic.cp."). Reference with cp_route('mister_chameleon.index').
|
*/

Route::get('mister-chameleon', [DashboardController::class, 'index'])
    ->name('mister_chameleon.index');
