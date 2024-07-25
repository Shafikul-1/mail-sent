<?php

namespace App\Http\Controllers;

use Google\Client;
use App\Models\User;
use Google\Service\Gmail;
use Google\Service\Oauth2;
use App\Models\GoogleToken;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Google_Service_Exception;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
            $genaratePass = "GoogleLoginGenaratePass123";
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $genaratePass]
            );

            // Save or update the token
            GoogleToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => json_encode($token),
                    'access_info' => json_encode($accessInfo)
                ]
            );

            // User Login
            $loginUser = Auth::attempt([
                'email' => $email,
                'password' => $genaratePass,
            ]);

            // Check login
            if ($loginUser) {
                return redirect()->route('home')->with('msg', 'User Logged In Successful');
            } else {
                return redirect()->route('home')->with('msg', 'Someting Want Wrong Login User');
            }
        } catch (\Throwable $th) {
            return redirect()->route('home')->with('msg', "authError" . $th->getMessage());
        }
    }

    // GEt All Gail
    public function getMail()
    {
        $accessCheck = $this->checkAccess();
        if (!$accessCheck) {
            return redirect()->route('home')->with('msg', "checkAccess denided");
        }

        // $gmail = new \Google\Service\Gmail($this->client);
        $gmail = new Gmail($this->client);
        $inbox = $gmail->users_messages->listUsersMessages('me');

        return view('gmail.inbox.inboxMessages', ['inboxMessage' => $inbox->getMessages()]);
        // return $data;
    }

    // inbox Single message View
    public function singleInboxMessage($messageId)
    {
        try {
            $dbgoogleToken = GoogleToken::where('user_id', Auth::user()->id)->get();
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
            $dbgoogleToken = GoogleToken::where('user_id', Auth::user()->id)->get();
            if (!$dbgoogleToken[0]) {
                return redirect()->route('home')->with('msg', "Token Is null");
            }
            $this->client->setAccessToken(json_decode($dbgoogleToken[0]->access_token, true));

            // Create Gmail service
            $gmail = new Gmail($this->client);
            $sentEmailData = $gmail->users_messages->listUsersMessages('me', ['q' => 'is:sent']);
            $sentMessage = $sentEmailData->getMessages();

            $filterDataArr = [];
            foreach ($sentMessage as $value) {
                $messageData = [
                    'id' => $value->id,
                    'threadId' => $value->threadId,
                    // 'historyId' => $value->historyId,
                    // 'internalDate' => $value->internalDate,
                    // 'labelIds' => $value->labelIds,
                    // 'raw' => $value->raw,
                    // 'sizeEstimate' => $value->sizeEstimate,
                    // 'snippet' => $value->snippet,
                ];
                $filterDataArr[$messageData['threadId']][] = $messageData;
            }

            $filterAllData = [];
            foreach ($filterDataArr as $singleData) {
                $lastMessageId = $singleData[0]['id'];
                $messageDetails = $gmail->users_messages->get('me', $lastMessageId);
                $headers = $messageDetails->getPayload()->getHeaders();

                // get other information 
                $reciverEmail = '';
                $subject = '';
                $sentDate = '';
                $messageContent = '';
                foreach ($headers as $otherInfo) {
                    if ($otherInfo->name === 'To') {
                        $reciverEmail = $otherInfo->value;
                    }
                    if ($otherInfo->name === 'Date') {
                        $sentDate = $otherInfo->value;
                    }
                    if ($otherInfo->name === 'Subject') {
                        $subject = $otherInfo->value;
                    }
                }

                // Get body data
                $parts = $messageDetails->getPayload()->getParts();
                if (is_null($parts)) {
                    $messageDetails->getPayload()->getBody()->getData();
                } else {
                    foreach ($parts as $part) {
                        if ($part->getmimeType() == 'text/plain') {
                            $messageContent = $part->getBody()->getData();
                            break;
                        }
                        // else{
                        //     $messageContent = $part->getBody()->getData();
                        //     break;
                        // }
                    }
                }

                $messageContent = base64_decode(strtr($messageContent, '-_', '+/'));
                $searchString = "wrote:";
                if (strpos($messageContent, $searchString) !== false) {
                    $messageContent = strstr($messageContent, $searchString, true);
                    $searchOn = "On";
                    $messageContent = strstr($messageContent, $searchOn, true);
                }

                $messageContent = preg_replace('/\s+/', ' ', $messageContent);
                $stringSort = explode(' ', trim($messageContent));
                if (str_word_count($messageContent) >= 5) {
                    // Get the first 5 words
                    $okString = array_slice($stringSort, 0, 5);
                    $result = implode(' ', $okString) . '...';
                } else {
                    $result = $messageContent;
                }

                // Only First block html get 👉 Use the HTML and remove the quoted replies
                // if (!empty($messageContent) && $part->getMimeType() == 'text/html') {
                //     $dom = new DOMDocument();
                //     @$dom->loadHTML($messageContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                //     $xpath = new DOMXPath($dom);
                //     $nodesToRemove = [];
                //     foreach ($xpath->query("//div[contains(@class, 'gmail_quote')]") as $node) {
                //         $nodesToRemove[] = $node;
                //     }
                //     foreach ($nodesToRemove as $node) {
                //         $node->parentNode->removeChild($node);
                //     }
                //     $messageContent = $dom->saveHTML();
                // }

                // array push data
                $totalData = end($singleData);
                $totalData['reciverEmail'] = $reciverEmail;
                $totalData['subject'] = $subject;
                $totalData['sentDate'] = $sentDate;
                $totalData['messageContent'] = $result;
                $totalData['total_message'] = count($singleData);
                $filterAllData[] = $totalData;
            }

            return view('gmail.sent.sentMessages', compact('filterAllData'));
            // return $filterAllData;

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

                // Create the raw MIME message
                $replyMessage = new Message();
                $rawMessage = "From: " . $this->getHeader($originalMessage, 'From') . "\r\n";
                $rawMessage .= "To: " . $this->getHeader($originalMessage, 'To') . "\r\n";
                $rawMessage .= "Subject: Re: " . $this->getHeader($originalMessage, 'Subject') . "\r\n";
                $rawMessage .= "In-Reply-To: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "References: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
                $rawMessage .= "Content-Transfer-Encoding: quoted-printable\r\n";
                $rawMessage .= "\r\n" . $replyText;
                // return $rawMessage;
                $rawMessageEncode = base64_encode($rawMessage);
                $plainRawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessageEncode);

                $replyMessage->setRaw($plainRawMessage);
                $replyMessage->setThreadId($threadId);
                $sentMessage = $gmail->users_messages->send('me', $replyMessage);

                return response()->json(['message' => 'Reply sent successfully']);
            } catch (\Google\Service\Exception $e) {
                Log::error('Error sending message:', ['error' => $e->getMessage()]);
                // return response()->json(['error' => $e->getMessage()]);
                return $e->getMessage();
            }
        } else {
            return redirect()->route('home')->with('msg', "Token Expired");
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
        $userId = Auth::user()->id;
        $dbgoogleToken = GoogleToken::where('user_id', $userId)->first();
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
            GoogleToken::where('user_id', $userId)->update(['access_token' => $newToken]);
            $tokenData = $this->client->getAccessToken(); // Update tokenData after refresh
        }
        return true;
    }
}
