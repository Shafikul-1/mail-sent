<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GmailController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(config('google.redirect_uri'));
        $client->addScope(Gmail::GMAIL_READONLY);

        return redirect($client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(config('google.redirect_uri'));
        $client->authenticate($request->get('code'));

        $token = $client->getAccessToken();
        Session::put('access_token', $token);

        return redirect('/read-emails');
    }

    public function readEmails()
    {
        $client = new Client();
        $client->setAccessToken(Session::get('access_token'));

        $service = new Gmail($client);

        $messages = $service->users_messages->listUsersMessages('me');

        return view('emails.index', ['messages' => $messages]);
    }

    public function sendEmail(Request $request)
    {
        $client = new Client();
        $client->setAccessToken(Session::get('access_token'));

        $service = new Gmail($client);

        $message = new \Google\Service\Gmail\Message();

        $rawMessageString = "From: you@example.com\r\n";
        $rawMessageString .= "To: {$request->to}\r\n";
        $rawMessageString .= "Subject: {$request->subject}\r\n\r\n";
        $rawMessageString .= $request->message;

        $rawMessage = base64_encode($rawMessageString);
        $message->setRaw($rawMessage);

        $service->users_messages->send('me', $message);

        return redirect()->back()->with('success', 'Email sent successfully!');
    }
}
