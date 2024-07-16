<?php

namespace App\Http\Controllers;

use Google\Client;
use App\Models\User;
use Google\Service\Gmail;
use App\Models\GoogleToken;
use Illuminate\Http\Request;
use Google_Service_Exception;
use Google\Service\Gmail\Message;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GmailController extends Controller
{
    private $client;
    // SEt Client
    public function __construct()
    {
        $applicationName = "my projet";
        $this->client = new Client();
        $this->client->setApplicationName($applicationName);
        $this->client->setAuthConfig(storage_path('app/client_secrate.json'));
        $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.modify');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.send');
        $this->client->setRedirectUri('http://127.0.0.1:8000/gmail/callback');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setScopes(
            [
                \Google\Service\Oauth2::USERINFO_PROFILE,
                \Google\Service\Oauth2::USERINFO_EMAIL,
                \Google\Service\Oauth2::OPENID,
            ]
        );
        $this->client->setIncludeGrantedScopes(true);
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
            return redirect()->route('home')->with('msg', "Code is missing");
        }

        $this->client->setRedirectUri(route('handleGoogleCallback'));

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) {
                return redirect()->route('home')->with('msg', "Error: " . $token['error']);
            }

            // Save the refresh token if it's available
            if (isset($token['refresh_token'])) {
                Session::put('google_refresh_token', $token['refresh_token']);
            }

            // Set the access token
            $this->client->setAccessToken($token['access_token']);

            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = Session::get('google_refresh_token');
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    // Update or store the new access token
                    $newAccessToken = $this->client->getAccessToken();
                    $this->client->setAccessToken($newAccessToken);
                } else {
                    return redirect()->route('home')->with('msg', "Refresh token is missing.");
                }
            }

            // Fetch user information
            $google_oauth = new Oauth2($this->client);
            $google_account_info = $google_oauth->userinfo->get();
            // Extract user details
            $userId = $google_account_info->id;
            $email = $google_account_info->email;
            $verifiedEmail = $google_account_info->verifiedEmail;
            $name = $google_account_info->name;
            $givenName = $google_account_info->givenName;
            $familyName = $google_account_info->familyName;
            $picture = $google_account_info->picture;
            $locale = $google_account_info->locale;
            $hostedDomain = $google_account_info->hd;
            $gender = $google_account_info->gender;
            $birthday = $google_account_info->birthday;
            $profileUrl = $google_account_info->profile;
            $emailVerifiedByUser = $google_account_info->emailVerified;
            $googleProfileLink = $google_account_info->link;

            $accessInfo = [
                'id' => $userId,
                'email' => $email,
                'verifiedEmail' => $verifiedEmail,
                'name' => $name,
                'givenName' => $givenName,
                'familyName' => $familyName,
                'picture' => $picture,
                'locale' => $locale,
                'hostedDomain' => $hostedDomain,
                'gender' => $gender,
                'birthday' => $birthday,
                'profileUrl' => $profileUrl,
                'emailVerifiedByUser' => $emailVerifiedByUser,
                'googleProfileLink' => $googleProfileLink,
            ];


            // Check if the user exists
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => rand(20, 300)]
            );

            // Save or update the token
            GoogleToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => json_encode($token),
                    'access_info' => json_encode($accessInfo)
                ]
            );

            return redirect()->route('home')->with('msg', "Auth Successful");
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
                $replyText = $request->input('reply');

                // Get the original message content
                $originalMessagePayload = $originalMessage->getPayload();
                $parts = $originalMessagePayload->getParts();
                $body = '';
                foreach ($parts as $part) {
                    if ($part->getMimeType() == 'text/html') {
                        $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                        break;
                    } elseif ($part->getMimeType() == 'text/plain') {
                        $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                        break;
                    }
                }

                // Create the raw MIME message
                $replyMessage = new Message();
                $rawMessage = "From: " . $this->getHeader($originalMessage, 'From') . "\r\n";
                $rawMessage .= "To: " . $this->getHeader($originalMessage, 'To') . "\r\n";
                $rawMessage .= "Subject: Re: " . $this->getHeader($originalMessage, 'Subject') . "\r\n";
                $rawMessage .= "In-Reply-To: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "References: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
                $rawMessage .= "Content-Transfer-Encoding: quoted-printable\r\n";
                $rawMessage .= "\r\n" . $replyText . "<br><br>" . $body;

                $rawMessageEncode = base64_encode($rawMessage);
                $plainRawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessageEncode);

                $replyMessage->setRaw($plainRawMessage);
                $replyMessage->setThreadId($threadId);
                $sentMessage = $gmail->users_messages->send('me', $replyMessage);

                return response()->json(['message' => 'Reply sent successfully']);
            } catch (\Google\Service\Exception $e) {
                Log::error('Error sending message:', ['error' => $e->getMessage()]);
                return response()->json(['error' => $e->getMessage()]);
            }
        } else {
            return redirect()->route('home')->with('msg', "Token Expired");
        }
    }

    // public function messageSent(Request $request, $messageId)
    // {
    //     $checking = $this->checkAccess();
    //     if ($checking) {
    //         $googleService = new Gmail($this->client);
    //         try {

    //             // receive the message body and extract it's headers
    //             $message = $googleService->users_messages->get('me', $messageId);
    //             $messageDetails = $message->getPayload();
    //             $messageHeaders = $messageDetails->getHeaders();

    //             // get the subject from the original message header
    //             $subject = 'Re:'.$this->getHeader($messageHeaders, 'Subject');

    //             // if you use the from header, this may contain the complete email address like John Doe <john.doe@foobar.com> - phpMailer will not accept that, the tricky thing is: you will not notice it, because it will be left blank and the Gmail API will return an "Recipient address required"
    //             preg_match('/.*<(.*@.*)>/', $this->getHeader($messageHeaders, 'From'),$to);

    //             // now use the PHPMailer to build a valid email-body
    //             $mail = new PHPMailer();
    //             $mail->CharSet = 'UTF-8';
    //             $mail->From = $from;
    //             $mail->FromName = $fromName;
    //             $mail->addAddress($to[1]);
    //             $mail->Subject = $subject;
    //             $mail->Body = $body;
    //             // preSend will build and verify the email
    //             $mail->preSend();

    //             $mime = $mail->getSentMIMEMessage();
    //             // the base64-url-encode is important, otherwise you'll receive an "Invalid value for ByteString" error
    //             $raw = base64url_encode($mime);

    //             // now use the Gmail-Message object to actually 
    //             // for me it is not clear, why we cannot use Class Google_Service_Gmail for this
    //             $message = new Google_Service_Gmail_Message();

    //             $message->setRaw($raw);

    //             $message->setThreadId($emailId);

    //             // and finally provide encoded message and user to our global google service object - this will send the email
    //             $response = $googleService->users_messages->send($user, $message);

    //         } catch (Google_Service_Exception $e) {
    //         return $e;
    //         }
    //     } else {
    //         return "not wokr check accesss";
    //     }
    // }

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
