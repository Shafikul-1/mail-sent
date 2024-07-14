<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GmailController extends Controller
{
    private $client;
    // SEt Client
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/client_secrate.json'));
        $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $this->client->setRedirectUri('http://127.0.0.1:8000/gmail/callback');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    // REdirect Working
    public function redirectToGoogle()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    // Authentecation All Handle code
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('home')->with('msg', "Code is messing");
        }

        $this->client->setRedirectUri(route('handleGoogleCallback'));

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) {
                return redirect()->route('home')->with('msg', "Error Code is messing" . $token['error']);
            }

            Session::put('google_token', $token);

            $token_expiry = now()->addSeconds($token['expires_in']);
            $refresh_token = $token['refresh_token'];
            $tokenSave = GoogleToken::create([
                'access_token' => json_encode($token),
                'refresh_token' => $refresh_token,
                'token_expiry' => $token_expiry,
            ]);

            return redirect()->route('home')->with('msg', "auth Successful");
        } catch (\Throwable $th) {
            return redirect()->route('home')->with('msg', "authError" . $th->getMessage());
        }
    }
}
