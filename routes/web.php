<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManagerInvitationController;

//welcome
Route::get('/', function () {
    return view('welcome');
});


// Manager Invitation Routes (Public)
Route::prefix('manager/invitation')->name('manager.invitation.')->group(function () {
    Route::get('/{token}', [ManagerInvitationController::class, 'show'])
        ->name('accept');
    
    Route::post('/{token}/accept', [ManagerInvitationController::class, 'accept'])
        ->name('accept.submit');
    
    Route::post('/{token}/decline', [ManagerInvitationController::class, 'decline'])
        ->name('decline');
});