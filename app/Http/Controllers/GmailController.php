<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class GmailController extends Controller
{
    private $client;
    // SEt Client
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/client_secrate.json'));
        $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.modify');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.send');
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

    // inbox Single message View
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
            return view('gmail.inbox.singleMessageDetails', compact('messageData'));
            // return $messageData;

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    // Sent Message All
    public function sentAllMessage()
    {
        try {
            $dbgoogleToken = GoogleToken::where('user_id', 1)->get();
            if (!$dbgoogleToken[0]) {
                return redirect()->route('home')->with('msg', "Token Is null");
            }
            $this->client->setAccessToken(json_decode($dbgoogleToken[0]->access_token, true));

            // Create Gmail service
            $gmail = new Gmail($this->client);
            $sentEmailData = $gmail->users_messages->listUsersMessages('me', ['q' => 'is:sent']);
            $sentMessage = $sentEmailData->getMessages();
            return view('gmail.sent.sentMessages', compact('sentMessage'));
            // return $allsentMessage;

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    // Sent Single message View
    public function singleSentMessage($messageId)
    {
        try {
            $dbgoogleToken = GoogleToken::where('user_id', 1)->get();
            if (!$dbgoogleToken[0]) {
                return redirect()->route('home')->with('msg', "Token Is null");
            }
            $this->client->setAccessToken(json_decode($dbgoogleToken[0]->access_token, true));

            // Create Gmail service
            $gmail = new Gmail($this->client);
            $fullDetails = $gmail->users_messages->get('me', $messageId);
            // ----------------


            $messageDate = $fullDetails->getInternalDate();
            $subject = '';
            foreach ($fullDetails->getPayload()->getHeaders() as $header) {
                if ($header->getName() == 'Subject') {
                    $subject = $header->getValue();
                    break;
                }
            }

            $bodyData = '';
            $attachMent = [];

            $processPart = function ($parts) use (&$bodyData, &$attachMent, &$processPart) {
                foreach ($parts as $part) {
                    if ($part->getMimeType() == 'text/html') {
                        if ($part->getBody() && $part->getBody()->getData()) {
                            // $bodyData .= $part->getBody()->getData();
                            // $bodyData .= base64_decode($part->getBody()->getData());
                            $bodyData .= base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                        }
                    }

                    if ($part->getFilename()) {
                        $attachMent[] = [
                            'fileName' => $part->getFilename(),
                            'fileId' => $part->getBody()->getAttachmentId(),
                        ];
                    }

                    if ($part->getParts()) {
                        $processPart($part->getParts());
                    }
                }
            };

            $payload = $fullDetails->getPayload();
            $processPart([$payload]);

            // $messageData = $fullDetails->toSimpleObject();
            // return $messageData;

            // Pass data to blade view
            return view('gmail.sent.singleMessageDetails', [
                'messageId' => $messageId,
                'messageDate' => $messageDate,
                'subject' => $subject,
                'attachments' => $attachMent,
                'bodyData' => $bodyData,
            ]);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    // sent message view page
    public function sentMessageReply($messageId)
    {
        return view('gmail.sent.sentMesssageReply', compact('messageId'));
    }

    // sent message reply
    // public function messageSent(Request $request, $messageId){
    //     $dbgoogleToken = GoogleToken::where('user_id', 1)->get();
    //     if (!$dbgoogleToken[0]) {
    //         return redirect()->route('home')->with('msg', "Token Is null");
    //     }
    //     $this->client->setAccessToken(json_decode($dbgoogleToken[0]->access_token, true));

    //     // Check if token has the required scopes
    //     if ($this->client->isAccessTokenExpired()) {
    //         $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
    //         $newToken =  json_encode($this->client->getAccessToken());
    //         GoogleToken::where('user_id', 1)->update([
    //             'access_token' => $newToken,
    //         ]);
    //     }

    //     if (!$this->client->isAccessTokenExpired()) {
    //         $requiredScopes = ['https://www.googleapis.com/auth/gmail.send'];
    //         $currentScopes = $this->client->getScopes();

    //         $missingScopes = array_diff($requiredScopes, $currentScopes);

    //         if (!empty($missingScopes)) {
    //             // If token doesn't have the required scope, force reauthorization
    //             $authUrl = $this->client->createAuthUrl();
    //             return redirect($authUrl);
    //         }
    //     }


    //     // Create Gmail service
    //     $gmail = new Gmail($this->client);
    //     try {
    //         $originalMessage = $gmail->users_messages->get('me', $messageId);
    //         $threadId = $originalMessage->getThreadId();
    //         // echo $threadId . "<br>";
    //         // Prepare Replay Message
    //         $replyText = $request->input('reply');
    //         // echo $replyText . "<br>";

    //         $replyMessage = new Message();

    //         $rawMessage = "To: " . $this->getHeader($originalMessage, 'From') . "\r\n";
    //         $rawMessage .= "Subject: Re: " . $this->getHeader($originalMessage, 'Subject') . "\r\n";
    //         $rawMessage .= "In-Reply-To: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
    //         $rawMessage .= "References: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
    //         $rawMessage .= "\r\n" . $replyText;

    //         $rawMessageEncode = base64_encode($rawMessage);
    //         $plainRawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessageEncode);

    //         // echo $plainRawMessage;
    //         $replyMessage->setRaw($plainRawMessage);
    //         $replyMessage->setThreadId($threadId);

    //         // Sent The reply
    //         $sentMessage = $gmail->users_messages->send('me', $replyMessage);
    //         return response()->json(['message' => 'Reply sent successfully']);
    //     } catch (\Google_Service_Exception $th) {
    //         return $th->getMessage();
    //         // return response()->json(['error' => $th->getMessage()]);
    //     }
    // }

    // Send message reply
    public function messageSent(Request $request, $messageId)
    {
        $checking = $this->checkAccess();
        if ($checking) {
            // Create Gmail service
            $gmail = new Gmail($this->client);
            try {
                $originalMessage = $gmail->users_messages->get('me', $messageId);
                $threadId = $originalMessage->getThreadId();

                // Prepare Reply Message
                $replyText = $request->input('reply');

                $replyMessage = new Message();

                
                $rawMessage = "To: " .  $this->getHeader($originalMessage, 'To')  . "\r\n";
                $rawMessage .= "Subject: Re: " . $this->getHeader($originalMessage, 'Subject') . "\r\n";
                $rawMessage .= "In-Reply-To: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "References: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "\r\n" . $replyText;
                
                // $messageData = $originalMessage->toSimpleObject();
                // return $messageData;


                $rawMessageEncode = base64_encode($rawMessage);
                $plainRawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessageEncode);

                $replyMessage->setRaw($plainRawMessage);
                $replyMessage->setThreadId($threadId);

                // Send the reply
                $sentMessage = $gmail->users_messages->send('me', $replyMessage);

                return response()->json(['message' => 'Reply sent successfully']);
            } catch (\Google\Service\Exception $e) {
                Log::error('Error sending message:', ['error' => $e->getMessage()]);
                return response()->json(['error' => $e->getMessage()]);
            }
        } else {
            return redirect()->route('home')->with('msg', "Token Expare");
        }
    }

    // search Information Headers
    private function getHeader($message, $name)
    {
        foreach ($message->getPayload()->getHeaders() as $header) {
            if ($header->getName() === $name) {
                return $header->getValue();
            }
        }
        return null;
    }

    // CHeck User Token Access ?
    private function checkAccess()
    {
        $dbgoogleToken = GoogleToken::where('user_id', 1)->first();
        if (!$dbgoogleToken) {
            return redirect()->route('home')->with('msg', "Token is null");
        }

        $tokenData = json_decode($dbgoogleToken->access_token, true);
        $this->client->setAccessToken($tokenData);

        // Check if token has expired
        if ($this->client->isAccessTokenExpired()) {
            Log::info('Access token expired. Refreshing token.');
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            $newToken = json_encode($this->client->getAccessToken());
            GoogleToken::where('user_id', 1)->update(['access_token' => $newToken]);
            $tokenData = $this->client->getAccessToken(); // Update tokenData after refresh
        }
        return true;
    }


}
