<?php

use App\Http\Controllers\ClientMailController;
use App\Http\Controllers\MailsettingController;
use App\Http\Controllers\testController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GmailController;

Route::get('/', function (){
    return view('home');
})->name('home');

Route::resource('mail', ClientMailController::class)->middleware('auth');
Route::resource('mailsetting', MailsettingController::class)->middleware('auth');
Route::resource('/user', UserController::class);

Route::get('/login', [UserController::class, 'login'])->name('login');
Route::get('/logout', [UserController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/send-mail', [ClientMailController::class, 'sendMail'])->name('sendMail')->middleware('auth');
Route::get('/unsend-mail', [ClientMailController::class, 'unSendMail'])->name('unSendMail')->middleware('auth');


Route::post('/authUser', [UserController::class, 'checkUser'])->name('authUser');
// Route::get('/sent', [testController::class, 'sentEmail'])->name('sent');
Route::get('/template', function(){
   return  view('mailtemplate');
});



// Gmail
Route::get('auth/google', [GmailController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GmailController::class, 'handleGoogleCallback']);
Route::get('read-emails', [GmailController::class, 'readEmails']);