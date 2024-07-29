<?php

namespace App\Http\Controllers;

use DateTime;
use DOMXPath;
use DOMDocument;
use Google\Client;
use App\Models\User;
use Google\Service\Gmail;
use Google\Service\Oauth2;
use App\Models\GoogleToken;
use Illuminate\Http\Request;
use Google_Service_Exception;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Google\Service\Gmail\MessagePart;
use Illuminate\Support\Facades\Session;
use Google\Service\Gmail\MessagePartBody;

class GmailController extends Controller
{
    private $client;
    // SEt Client
    public function __construct()
    {
        $applicationName = "my projet";
        $this->client = new Client();
        $this->client->setApplicationName($applicationName);
        $this->client->setAuthConfig(storage_path('app/comshafikul.runjila.json'));
        // $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        // $this->client->addScope('https://www.googleapis.com/auth/gmail.modify');
        // $this->client->addScope('https://www.googleapis.com/auth/gmail.send');
        $this->client->addScope(Gmail::MAIL_GOOGLE_COM); // General Gmail access scope
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->addScope(Gmail::GMAIL_MODIFY);
        $this->client->addScope(Gmail::GMAIL_SEND);

        $this->client->setRedirectUri(route('handleGoogleCallback'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        // $this->client->setScopes(
        //     [
        //         \Google\Service\Oauth2::USERINFO_PROFILE,
        //         \Google\Service\Oauth2::USERINFO_EMAIL,
        //         \Google\Service\Oauth2::OPENID,
        //     ]
        // );

        $this->client->setIncludeGrantedScopes(true);
    }

    // REdirect Working
    public function redirectToGoogle()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    // Authentecation All Handle code
    // public function handleGoogleCallback(Request $request)
    // {
    //     $code = $request->input('code');
    //     if (empty($code)) {
    //         return redirect()->route('home')->with('msg', "Code is missing");
    //     }

    //     $this->client->setRedirectUri(route('handleGoogleCallback'));

    //     try {
    //         $token = $this->client->fetchAccessTokenWithAuthCode($code);
    //         if (isset($token['error'])) {
    //             return redirect()->route('home')->with('msg', "Error: " . $token['error']);
    //         }

    //         // Save the refresh token if it's available
    //         if (isset($token['refresh_token'])) {
    //             Session::put('google_refresh_token', $token['refresh_token']);
    //         }

    //         // Set the access token
    //         $this->client->setAccessToken($token['access_token']);

    //         if ($this->client->isAccessTokenExpired()) {
    //             $refreshToken = Session::get('google_refresh_token');
    //             if ($refreshToken) {
    //                 $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
    //                 // Update or store the new access token
    //                 $newAccessToken = $this->client->getAccessToken();
    //                 $this->client->setAccessToken($newAccessToken);
    //             } else {
    //                 return redirect()->route('home')->with('msg', "Refresh token is missing.");
    //             }
    //         }

    //         // Fetch user information
    //         $google_oauth = new Oauth2($this->client);
    //         $google_account_info = $google_oauth->userinfo->get();
    //         // Extract user details
    //         $userId = $google_account_info->id;
    //         $email = $google_account_info->email;
    //         $verifiedEmail = $google_account_info->verifiedEmail;
    //         $name = $google_account_info->name;
    //         $givenName = $google_account_info->givenName;
    //         $familyName = $google_account_info->familyName;
    //         $picture = $google_account_info->picture;
    //         $locale = $google_account_info->locale;
    //         $hostedDomain = $google_account_info->hd;
    //         $gender = $google_account_info->gender;
    //         $birthday = $google_account_info->birthday;
    //         $profileUrl = $google_account_info->profile;
    //         $emailVerifiedByUser = $google_account_info->emailVerified;
    //         $googleProfileLink = $google_account_info->link;

    //         $accessInfo = [
    //             'id' => $userId,
    //             'email' => $email,
    //             'verifiedEmail' => $verifiedEmail,
    //             'name' => $name,
    //             'givenName' => $givenName,
    //             'familyName' => $familyName,
    //             'picture' => $picture,
    //             'locale' => $locale,
    //             'hostedDomain' => $hostedDomain,
    //             'gender' => $gender,
    //             'birthday' => $birthday,
    //             'profileUrl' => $profileUrl,
    //             'emailVerifiedByUser' => $emailVerifiedByUser,
    //             'googleProfileLink' => $googleProfileLink,
    //         ];


    //         // Check if the user exists
    //         $genaratePass = "GoogleLoginGenaratePass123";
    //         $user = User::firstOrCreate(
    //             ['email' => $email],
    //             ['name' => $name, 'password' => $genaratePass]
    //         );

    //         // Save or update the token
    //         GoogleToken::updateOrCreate(
    //             ['user_id' => $user->id],
    //             [
    //                 'access_token' => json_encode($token),
    //                 'access_info' => json_encode($accessInfo)
    //             ]
    //         );

    //         // User Login
    //         $loginUser = Auth::attempt([
    //             'email' => $email,
    //             'password' => $genaratePass,
    //         ]);

    //         // Check login
    //         if ($loginUser) {
    //             return redirect()->route('home')->with('msg', 'User Logged In Successful');
    //         } else {
    //             return redirect()->route('home')->with('msg', 'Someting Want Wrong Login User');
    //         }
    //     } catch (\Throwable $th) {
    //         return redirect()->route('home')->with('msg', $th->getMessage());
    //     }
    // }
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('home')->with('msg', "Code is missing");
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            Session::put('Gtoken', $token);

            if (isset($token['error'])) {
                return redirect()->route('home')->with('msg', "Error: " . $token['error']);
            }

            if (isset($token['refresh_token'])) {
                Session::put('google_refresh_token', $token['refresh_token']);
            }

            $this->client->setAccessToken($token);

            // Ensure token has correct scopes
            $scopes = $this->client->getScopes();
            Log::info('Granted Scopes: ' . json_encode($scopes));

            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = Session::get('google_refresh_token');
                if ($refreshToken) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->client->setAccessToken($newToken);
                    Session::put('Gtoken', $newToken);
                    Log::info('New Access Token: ' . json_encode($newToken));
                } else {
                    return redirect()->route('home')->with('msg', "Refresh token is missing.");
                }
            }

            // Fetch user information
            $google_oauth = new Oauth2($this->client);
            $google_account_info = $google_oauth->userinfo->get();

            // Handle user information
            $user = User::firstOrCreate(
                ['email' => $google_account_info->email],
                ['name' => $google_account_info->name, 'password' => 'GoogleLoginGenaratePass123']
            );

            GoogleToken::updateOrCreate(
                ['user_id' => $user->id],
                ['access_token' => json_encode($token)]
            );

            Auth::login($user);

            return redirect()->route('home')->with('msg', 'User Logged In Successfully');
        } catch (\Throwable $th) {
            return redirect()->route('home')->with('msg', $th->getMessage());
        }
    }

    // GEt All Gail
    // public function getMail()
    // {
    //     $Gtoken = Session::get('Gtoken');
    //     $this->client->setAccessToken($Gtoken);

    //     if ($this->client->isAccessTokenExpired()) {
    //         $refreshToken = Session::get('google_refresh_token');
    //         if ($refreshToken) {
    //             $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
    //             // Update or store the new access token
    //             $newAccessToken = $this->client->getAccessToken();
    //             $this->client->setAccessToken($newAccessToken);

    //             // Log the new access token for debugging
    //             Log::info('New Access Token: ' . json_encode($newAccessToken));
    //         } else {
    //             return redirect()->route('home')->with('msg', "Refresh token is missing.");
    //         }
    //     }

    //     // $gmail = new \Google\Service\Gmail($this->client);
    //     $gmail = new Gmail($this->client);
    //     $inbox = $gmail->users_messages->listUsersMessages('me');

    //     return view('gmail.inbox.inboxMessages', ['inboxMessage' => $inbox->getMessages()]);
    //     // return $data;
    // }

    public function getMail()
    {
        $Gtoken = Session::get('Gtoken');
        $this->client->setAccessToken($Gtoken);

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = Session::get('google_refresh_token');
            if ($refreshToken) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $this->client->setAccessToken($newToken);
                Session::put('Gtoken', $newToken);
                Log::info('New Access Token: ' . json_encode($newToken));
            } else {
                return redirect()->route('home')->with('msg', "Refresh token is missing.");
            }
        }

        $gmail = new Gmail($this->client);
        $inbox = $gmail->users_messages->listUsersMessages('me');
        return $inbox->getMessages();
        return view('gmail.inbox.inboxMessages', ['inboxMessage' => $inbox->getMessages()]);
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
    public function sentAllMessage($pageId = null)
    {
        // Other function work
        $getData = $this->sentMailAllData($pageId);
        if ($getData == false) {
            return false;
        }

        $gmail = new Gmail($this->client);
        $uniqueMessageId = $getData['uniqueMessageId'];

        // This fucntion work
        try {
            $filterAllData = [];
            foreach ($uniqueMessageId as $messageId) {
                $currentIdMessageCount = count($messageId);
                $currentMessageId = $messageId[0];
                $replaceStr = "$currentMessageId";
                $messageDetails = $gmail->users_messages->get('me', $replaceStr);
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
                        $getDate = $otherInfo->value;
                        $date = new DateTime($getDate);
                        $sentDate = $date->format('l, d F Y \a\t h:i A');
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
                        if ($part->getmimeType() == 'text/plain' || $part->getmimeType() == 'text/html') {
                            $messageContent = $part->getBody()->getData();
                            break;
                        }
                    }
                }

                // Search String only fast message body data
                $messageContent = base64_decode(strtr($messageContent, '-_', '+/'));
                $searchString = "wrote:";
                if (strpos($messageContent, $searchString) !== false) {
                    $messageContent = strstr($messageContent, $searchString, true);
                    $searchOn = "On";
                    $messageContent = strstr($messageContent, $searchOn, true);
                }

                // big string sort
                // $messageContent = htmlspecialchars_decode(strip_tags($messageContent)); //html tag skip
                $messageContent = preg_replace('/\s+/', ' ', $messageContent);
                $stringSort = explode(' ', trim($messageContent));
                if (str_word_count($messageContent) >= 7) {
                    // Get the first 5 words
                    $okString = array_slice($stringSort, 0, 7);
                    $result = implode(' ', $okString);
                } else {
                    $result = $messageContent;
                }

                // array push data
                $totalData['id'] = $currentMessageId;
                $totalData['reciverEmail'] = $reciverEmail;
                $totalData['subject'] = $subject;
                $totalData['sentDate'] = $sentDate;
                $totalData['messageContent'] = $result;
                $totalData['totalMessage'] = $currentIdMessageCount;
                $filterAllData[] = $totalData;
            }

            // return [
            //     'filterAllData' => $filterAllData,
            //     'pageTokens' => $getData['pageTokens'],
            // ];
            return view('gmail.sent.sentMessages', ['filterAllData' => $filterAllData, 'pageTokens' => $getData['pageTokens']]);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    // Sent message All page id fetch
    private function sentMailAllData($pageId)
    {
        if ($this->checkGmailAccess()) {
            $gmail = new Gmail($this->client);
            $pageTokens = [];
            $pageToken = null;

            do {
                $optParams = ['q' => 'is:sent'];
                if ($pageToken) {
                    $optParams['pageToken'] = $pageToken;
                }

                $sentEmailData = $gmail->users_messages->listUsersMessages('me', $optParams);
                $messages = $sentEmailData->getMessages();

                // Store the current page token
                if ($pageToken) {
                    $pageTokens[] = $pageToken;
                }

                // Get the next page token
                $pageToken = $sentEmailData->getNextPageToken();
            } while ($pageToken);

            // Store the final page token if there is one
            if ($pageToken) {
                $pageTokens[] = $pageToken;
            }

            // Get All Email Message IDs
            $sentMessageIds = $gmail->users_messages->listUsersMessages('me', [
                'q' => 'is:sent',
                'pageToken' => $pageId ?: null
            ]);

            $sentMessage = $sentMessageIds->getMessages();
            $allMessageId = [];
            foreach ($sentMessage as $messageAllId) {
                $allMessageId[$messageAllId->threadId][] = $messageAllId->id;
            }

            return [
                'uniqueMessageId' => $allMessageId,
                'pageTokens' => $pageTokens,
            ];
        } else {
            return false;
        };
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

                // Extract original message details
                $from = $this->getHeader($originalMessage, 'From');
                $to = $this->getHeader($originalMessage, 'To');
                $subject = $this->getHeader($originalMessage, 'Subject');
                $messageIdHeader = $this->getHeader($originalMessage, 'Message-ID');
                $date = $this->getHeader($originalMessage, 'Date');
                $originalBody = $this->getBody($originalMessage);

                // Format the date
                $dateTime = new \DateTime($date);
                $formattedDate = $dateTime->format('D, M d, Y \a\t g:i A');

                // Format the "From" address with a mailto link if not already formatted
                if (preg_match('/(.*) <(.+)>/', $from, $matches)) {
                    $fromName = $matches[1];
                    $fromEmail = $matches[2];
                    $fromFormatted = "$fromName &lt;<a href='mailto:$fromEmail'>$fromEmail</a>&gt;";
                } else {
                    $fromFormatted = $from;
                }

                // Create the message parts
                $textPart = new MessagePart();
                $textPart->setMimeType('text/plain');
                $textPartBody = new MessagePartBody();
                $textPartBody->setData(base64_encode($replyText));
                $textPart->setBody($textPartBody);

                $htmlBody = $replyText . "<br><br>On $formattedDate, $fromFormatted wrote:<br><blockquote style='margin:0px 0px 0px 0.8ex;border-left:1px solid rgb(204,204,204);padding-left:1ex'>$originalBody</blockquote>";
                $htmlPart = new MessagePart();
                $htmlPart->setMimeType('text/html');
                $htmlPartBody = new MessagePartBody();
                $htmlPartBody->setData(base64_encode($htmlBody));
                $htmlPart->setBody($htmlPartBody);

                // Create the full message with parts
                $rawMessage = "From: $from\r\n";
                $rawMessage .= "To: $to\r\n";
                $rawMessage .= "Subject: Re: $subject\r\n";
                $rawMessage .= "In-Reply-To: $messageIdHeader\r\n";
                $rawMessage .= "References: $messageIdHeader\r\n";
                $rawMessage .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";
                $rawMessage .= "\r\n--boundary\r\n";
                $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $rawMessage .= $textPartBody->getData();
                $rawMessage .= "\r\n--boundary\r\n";
                $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
                $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $rawMessage .= $htmlPartBody->getData();
                $rawMessage .= "\r\n--boundary--";

                // Encode the message
                $rawMessageEncode = base64_encode($rawMessage);
                $plainRawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessageEncode);

                // Create and send the reply message
                $replyMessage = new Message();
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

    // Get Body Message
    private function getBody($message)
    {
        $parts = $message->getPayload()->getParts();
        foreach ($parts as $part) {
            if ($part->getMimeType() === 'text/html') {
                $data = $part->getBody()->getData();
                return base64_decode(strtr($data, '-_', '+/'));
            } elseif ($part->getMimeType() === 'multipart/alternative') {
                foreach ($part->getParts() as $subPart) {
                    if ($subPart->getMimeType() === 'text/html') {
                        $data = $subPart->getBody()->getData();
                        return base64_decode(strtr($data, '-_', '+/'));
                    }
                }
            }
        }
        return '';
    }

    // Check Gmail access
    private function checkGmailAccess()
    {
        $dbgoogleToken = GoogleToken::where('user_id', Auth::user()->id)->get();
        if (!$dbgoogleToken[0]) {
            return redirect()->route('home')->with('msg', "Token Is null");
        }
        $this->client->setAccessToken(json_decode($dbgoogleToken[0]->access_token, true));
        // Create Gmail service
        return true;
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
