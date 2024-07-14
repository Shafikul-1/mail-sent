<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use Google\Client;
use Google\Service\Gmail;
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

    // GEt All Gail
    public function getMail()
    {
        $dbgoogleToken = GoogleToken::where('user_id', 1)->get();
        if (!$dbgoogleToken[0]) {
            return redirect()->route('home')->with('msg', "Token Is null");
        }

        $this->client->setAccessToken(json_decode($dbgoogleToken[0]->access_token, true));

        if ($this->client->isAccessTokenExpired()) {
            if (!empty($dbgoogleToken[0]->refresh_token)) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($dbgoogleToken[0]->refresh_token);
                $dbgoogleToken[0]->access_token = json_encode($newToken);
                $dbgoogleToken[0]->token_expiry = now()->addSeconds($newToken['expires_in']);
                $dbgoogleToken[0]->save();
            } else {
                return redirect()->route('home')->with('msg', "Refresh token not found.");
            }
        }

        // $gmail = new \Google\Service\Gmail($this->client);
        $gmail = new Gmail($this->client);
        $inbox = $gmail->users_messages->listUsersMessages('me');

        // $data = [];
        // foreach ($inbox->getMessages() as $value) {
        //     $messageId = $value->getId();
        //     $messge = $gmail->users_messages->get('me', $messageId, ['format'=> 'full']);
        //     // $headers = $messge->getPayload()->getHeaders();
        //     $snippet = $messge->getSnippet();
        //     $data['userId'] = $messageId;
        //     $data['snippet'] = $snippet;
        //     // return $snippet;
        // }
        return view('gmail.inbox.inboxMessages', ['inboxMessage' => $inbox->getMessages()]);
        // return $data;
    }

    public function singleInboxMessage($messageId)
    {
        try {
            $dbgoogleToken = GoogleToken::where('user_id', 1)->get();
            if (!$dbgoogleToken[0]) {
                return redirect()->route('home')->with('msg', "Token Is null");
            }
            $this->client->setAccessToken(json_decode($dbgoogleToken[0]->access_token, true));

            // Create Gmail service
            $gmail = new Gmail($this->client);
            $fullDetails = $gmail->users_messages->get('me', $messageId, ['format' => 'full']);

            // $messageData = response()->json($fullDetails);
            $messageData = $fullDetails->toSimpleObject();
            return view('gmail.inbox.singleMessageDetails' , compact('messageData'));
            // return $messageData;

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
