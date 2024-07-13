<?php

use App\Http\Controllers\ClientMailController;
use App\Http\Controllers\Mail_messageController;
use App\Http\Controllers\MailsettingController;
use App\Http\Controllers\testController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return view('home');
})->name('home');

Route::resource('mail', ClientMailController::class)->middleware('auth');
Route::resource('mailsetting', MailsettingController::class)->middleware('auth');
Route::resource('/user', UserController::class);
Route::resource('mail-message', Mail_messageController::class)->middleware('auth');

Route::get('/login', [UserController::class, 'login'])->name('login');
Route::get('/logout', [UserController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/unsend-mail', [ClientMailController::class, 'unSendMail'])->name('unSendMail')->middleware('auth');


Route::post('/authUser', [UserController::class, 'checkUser'])->name('authUser');
// Route::get('/sent', [testController::class, 'sentEmail'])->name('sent');
Route::get('/template', function(){
   return  view('mailtemplate');
});