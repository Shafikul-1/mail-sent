<?php

use App\Http\Controllers\ClientMailController;
use App\Http\Controllers\MailsettingController;
use App\Http\Controllers\testController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\MailfileController;

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
Route::get('/gmail/auth', [GmailController::class, 'redirectToGoogle'])->name('redirectToGoogle');
Route::get('/gmail/callback', [GmailController::class, 'handleGoogleCallback'])->name('handleGoogleCallback');
Route::get('/gmail/inbox', [GmailController::class, 'getMail'])->name('getMail');
Route::get('gmail/inbox/{id}', [GmailController::class, 'singleInboxMessage'])->name('singleInboxMessage');
Route::get('/gmail/sent', [GmailController::class, 'sentAllMessage'])->name('sentAllMessage');
Route::get('/gmail/sent/{id}', [GmailController::class, 'singleSentMessage'])->name('singleSentMessage');
Route::get('/gmail/sent/reply/{messageId}', [GmailController::class, 'sentMessageReply'])->name('sentMessageReply');
Route::post('/gmail/sent-message/{messageId}', [GmailController::class, 'messageSent'])->name('messageSent');
Route::post('/gmail/multi-work', [GmailController::class, 'multiWork'])->name('multiWork');
Route::get('check', [MailfileController::class, 'index'])->name('check');