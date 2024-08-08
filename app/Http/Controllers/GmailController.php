<?php

namespace App\Http\Controllers;

use DateTime;
use Carbon\Carbon;
use DOMXPath;
use DOMDocument;
use Google\Client;
use App\Models\User;
use App\Models\Mailfile;
use Google\Service\Gmail;
use App\Models\MailSender;
use Google\Service\Oauth2;
use App\Models\GoogleToken;
use Illuminate\Http\Request;
use Google_Service_Exception;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Google\Service\Gmail\MessagePart;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Google\Service\Gmail\MessagePartBody;

class GmailController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('My Project');
        $this->client->setAuthConfig(storage_path('app/comshafikul.json'));
        $this->client->addScope([
            Gmail::MAIL_GOOGLE_COM, // General Gmail access scope
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_MODIFY,
            Gmail::GMAIL_SEND,
            Oauth2::USERINFO_PROFILE,
            Oauth2::USERINFO_EMAIL
        ]);
        $this->client->setRedirectUri(route('handleGoogleCallback'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setIncludeGrantedScopes(true);
    }

    // Google Redirect
    public function redirectToGoogle()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    // Handle Google
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('home')->with('msg', 'Code is missing');
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) {
                return redirect()->route('home')->with('msg', 'Error: ' . $token['error']);
            }

            Session::put('Gtoken', $token);
            if (isset($token['refresh_token'])) {
                Session::put('google_refresh_token', $token['refresh_token']);
            }

            $this->client->setAccessToken($token);

            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = Session::get('google_refresh_token');
                if ($refreshToken) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->client->setAccessToken($newToken);
                    Session::put('Gtoken', $newToken);
                } else {
                    return redirect()->route('home')->with('msg', 'Refresh token is missing.');
                }
            }

            $google_oauth = new Oauth2($this->client);
            $google_account_info = $google_oauth->userinfo->get();
            if (!$google_account_info->email) {
                return redirect()->route('home')->with('msg', 'Unable to retrieve user email from Google.');
            }

            $user = User::firstOrCreate(
                ['email' => $google_account_info->email],
                ['name' => $google_account_info->name, 'password' => "EmailPass"]
            );

            GoogleToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => json_encode($token),
                    'access_info' => json_encode([
                        'id' => $google_account_info->id,
                        'name' => $google_account_info->name,
                        'email' => $google_account_info->email,
                        'picture' => $google_account_info->picture,
                        'profile' => $google_account_info->profile,
                        'gender' => $google_account_info->gender,
                        'birthday' => $google_account_info->birthday,
                        'googleProfileLink' => $google_account_info->link,
                    ])
                ]
            );

            Auth::login($user);
            return redirect()->route('home')->with('msg', 'User Logged In Successfully');
        } catch (\Exception $e) {
            Log::error('Error fetching user info: ' . $e->getMessage());
            return redirect()->route('home')->with('msg', 'Error: ' . $e->getMessage());
        }
    }

    // Compose Email
    public function compose()
    {
        return view('gmail.compose');
    }


    // public function sentding()
    // {
    //     $uerId = Auth::user()->id;

    //     $dbgoogleToken = GoogleToken::where('user_id', $uerId)->first();
    //     if (!$dbgoogleToken) {
    //         return false;
    //     }

    //     $tokenData = json_decode($dbgoogleToken->access_token, true);
    //     $this->client->setAccessToken($tokenData);

    //     // Check if token has expired
    //     if ($this->client->isAccessTokenExpired()) {
    //         $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
    //         $newToken = json_encode($this->client->getAccessToken());
    //         GoogleToken::where('user_id', $uerId)->update(['access_token' => $newToken]);
    //         $tokenData = $this->client->getAccessToken(); // Update tokenData after refresh
    //     }
    //     $currentTime = now()->format('Y-m-d H:i:s');
    //     $currentAllData = MailSender::whereRaw("status = ? AND sendingTime <= ?", [0, $currentTime])->get();
    //     foreach ($currentAllData as $data) {
    //         try {
    //             $service = new Gmail($this->client);
    //             $message = new Message();
    //             $message->setRaw($data->email_content);
    //             $service->users_messages->send('me', $message);

    //             MailSender::where('id', $data->id)->delete();
    //             echo "successful sent";
    //         } catch (Google_Service_Exception $e) {
    //             Log::error('Error sending email: ' . $e->getMessage());
    //             echo "FAiled sent";
    //         }
    //     }
    // }


    // $currentTime = now();
    //         $email = $this->createEmailWithAttachments($client_mail, $request->input('subject'), $request->input('message'), $attachmentPaths);
    // MailSender::create([
    //     'client_email' => $client_mail,
    //     'user_id' => $user_id,
    //     'sendingTime' => $currentTime->addMinutes($intervalMinutes)->format('Y-m-d H:i:s'),
    //     'status' => false,
    //     'email_content' => $email,
    // ]);
    // $intervalMinutes += $request->sendingTime;

    // Compose Email Sent
    public function composeSent(Request $request)
    {
        // return $request;
        ini_set('max_excution_time', 120);

        $user_id = Auth::user()->id;

        // Validate the request
        $validatedData = $request->validate([
            'to' => 'required',
            'subject' => 'required',
            'sendingTime' => 'required|numeric',
            'send_times' => 'required|date',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,mp4,avi|max:20480' // Increased max size
        ]);

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

        $service = new Gmail($this->client);
        $attachmentPaths = [];

        if ($request->file('attachments')) {
            foreach ($request->file('attachments') as $value) {
                $originalName = pathinfo(time() . '_' . $value->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $value->getClientOriginalExtension();
                $path = $value->storeAs('attachments', $originalName, 'public');
                Mailfile::create([
                    'all_files_name' => $path,
                    'user_id' => $user_id,
                ]);
                array_push($attachmentPaths, $path);
            }
        }

        // Parse the email body for embedded files (e.g., images, videos)
        $messageBody = $request->input('message');
        $messageBody = $this->handleEmbeddedFiles($messageBody, $user_id, $attachmentPaths);

        $allMails = array_filter(explode(' ', $request->to), function($value){
            return !empty(trim($value));
        });
        $intervalMinutes = $request->sendingTime;
// return $attachmentPaths;
        foreach ($allMails as $client_mail) {
            // $currentTime = now();
            $currentTime = Carbon::parse($request->send_times);
            // $email = $this->createEmailWithAttachments($client_mail, $request->input('subject'), $messageBody, $attachmentPaths);

            MailSender::create([
                'client_email' => $client_mail,
                'attachmentPaths' => $attachmentPaths,
                'subject' => $request->subject,
                'user_id' => $user_id,
                'sendingTime' => $currentTime->addMinutes($intervalMinutes),
                'status' => false,
                'email_content' => $messageBody,
            ]);
            $intervalMinutes += $request->sendingTime;

            // try {
            //     $message = new Message();
            //     $message->setRaw($email);
            //     $service->users_messages->send('me', $message);
            //     // return redirect()->route('home')->with('msg', 'Email sent successfully');
            // } catch (Google_Service_Exception $e) {
            //     Log::error('Error sending email: ' . $e->getMessage());
            // }
        }

        return redirect()->route('home')->with('msg', "Mail sending pending, saved to database");
    }

    // Handle embedded files in the email body
    private function handleEmbeddedFiles($messageBody, $user_id, &$attachmentPaths)
    {
        // Regular expression to find embedded files (e.g., <img src="data:image/png;base64,...">)
        $pattern = '/<img src="data:(image\/\w+);base64,([^"]+)">/i';

        if (preg_match_all($pattern, $messageBody, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                $mimeType = $match[1];
                $base64Data = $match[2];
                $fileData = base64_decode($base64Data);
                $extension = explode('/', $mimeType)[1];
                $fileName = uniqid() . '.' . $extension;
                $filePath = storage_path('app/public/attachments/' . $fileName);
                file_put_contents($filePath, $fileData);

                Mailfile::create([
                    'all_files_name' => 'attachments/' . $fileName,
                    'user_id' => $user_id,
                ]);

                array_push($attachmentPaths, 'attachments/' . $fileName);

                // Replace the embedded file with a CID (Content-ID)
                $messageBody = str_replace($match[0], '<img src="cid:' . $fileName . '">', $messageBody);
            }
        }

        return $messageBody;
    }

    // Compose email with attachments
    // private function createEmailWithAttachments($to, $subject, $messageText, $attachmentPaths)
    // {
    //     $boundary = uniqid(rand(), true);
    //     $subject = "=?utf-8?B?" . base64_encode($subject) . "?=";
    //     $fromName = Auth::user()->name;
    //     $fromEmail = Auth::user()->email;
    //     $from = "=?utf-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";

    //     $rawMessage = "From: {$from}\r\n";
    //     $rawMessage .= "To: {$to}\r\n";
    //     $rawMessage .= "Subject: {$subject}\r\n";
    //     $rawMessage .= "MIME-Version: 1.0\r\n";
    //     $rawMessage .= "Content-Type: multipart/related; boundary=\"{$boundary}\"\r\n\r\n";

    //     // Plain text message
    //     $rawMessage .= "--{$boundary}\r\n";
    //     $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    //     $rawMessage .= "{$messageText}\r\n";

    //     foreach ($attachmentPaths as $path) {
    //         $filePath = storage_path("app/public/{$path}");
    //         if (!file_exists($filePath)) {
    //             continue; // Skip if the file doesn't exist
    //         }
    //         $fileName = basename($filePath);
    //         $fileData = file_get_contents($filePath);
    //         $base64File = base64_encode($fileData);
    //         $mimeType = mime_content_type($filePath);

    //         // Attachment
    //         $rawMessage .= "--{$boundary}\r\n";
    //         $rawMessage .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
    //         $rawMessage .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
    //         $rawMessage .= "Content-ID: <{$fileName}>\r\n";
    //         $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
    //         $rawMessage .= chunk_split($base64File, 76, "\r\n");
    //     }

    //     $rawMessage .= "--{$boundary}--";

    //     return rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
    // }


    // Get Email All
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
        // return $inbox->getMessages();
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
        $getData = $this->sentMailAllData();
        if ($getData == false) {
            return false;
        }
        $pageAllData = [];
        foreach ($getData['paginatedResults'] as $allData) {
            if ($pageId == null || $pageId == 0) {
                if ($allData['page'] == 0) {
                    $pageAllData[] = $allData['threads'];
                    break;
                }
            }

            if ($allData['page'] == $pageId) {
                $pageAllData[] = $allData['threads'];
                break;
            }
        }

        $oneMessageId = [];
        foreach ($pageAllData as $messageAllId) {
            $oneMessageId = $messageAllId;
        }
        // return $pageAllData;
        // $uniqueMessageId = $getData['uniqueMessageId'];

        // This fucntion work
        $gmail = new Gmail($this->client);
        try {
            $filterAllData = [];
            foreach ($oneMessageId as $messageId) {
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
    private function sentMailAllData()
    {
        set_time_limit(120);
        if ($this->checkGmailAccess()) {
            $gmail = new Gmail($this->client);
            $pageTokens = [];
            $pageToken = null;
            $allMessageId = [];

            do {
                $optParams = ['q' => 'is:sent'];
                if ($pageToken) {
                    $optParams['pageToken'] = $pageToken;
                }

                $sentEmailData = $gmail->users_messages->listUsersMessages('me', $optParams);
                $messages = $sentEmailData->getMessages();

                // Process the messages and group by threadId
                foreach ($messages as $message) {
                    $threadId = $message->threadId;
                    $messageId = $message->id;

                    if (!isset($allMessageId[$threadId])) {
                        $allMessageId[$threadId] = [];
                    }

                    $allMessageId[$threadId][] = $messageId;
                }

                // Get the next page token
                $pageToken = $sentEmailData->getNextPageToken();
            } while ($pageToken);

            $page = [];
            // Paginate the thread IDs
            $threadIdPages = array_chunk(array_keys($allMessageId), 49);
            $paginatedResults = [];

            foreach ($threadIdPages as $index => $threadIds) {
                $paginatedResults[] = [
                    'page' => $index,
                    'threads' => array_intersect_key($allMessageId, array_flip($threadIds)),
                ];
                $page[] = $index;
            }

            return [
                'paginatedResults' => $paginatedResults,
                'pageTokens' => $page,
            ];
        } else {
            return false;
        }
    }
    // Sent Single message View
    public function singleSentMessage($messageId)
    {
        $dbgoogleToken = GoogleToken::where('user_id', Auth::user()->id)->get();
        try {
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
