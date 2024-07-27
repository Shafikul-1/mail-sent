<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\testController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\MailfileController;
use App\Http\Controllers\OtherWorkController;
use App\Http\Controllers\ClientMailController;
use App\Http\Controllers\MailsettingController;

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
Route::get('/gmail/sent/{pageId?}', [GmailController::class, 'sentAllMessage'])->name('sentAllMessage');
Route::get('/gmail/sent-view/{id}', [GmailController::class, 'singleSentMessage'])->name('singleSentMessage');
Route::get('/gmail/sent/reply/{messageId}', [GmailController::class, 'sentMessageReply'])->name('sentMessageReply');
Route::post('/gmail/sent-message/{messageId}', [GmailController::class, 'messageSent'])->name('messageSent');
Route::get('check', [MailfileController::class, 'index'])->name('check');

// Other Work All
Route::resource('other-work', OtherWorkController::class);
Route::post('/gmail/multi-work', [OtherWorkController::class, 'multiWork'])->name('multiWork');