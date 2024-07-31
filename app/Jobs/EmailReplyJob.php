<?php

namespace App\Jobs;

use Google\Client;
use App\Models\User;
use App\Models\OtherWork;
use Google\Service\Gmail;
use App\Models\GoogleToken;
use Illuminate\Bus\Queueable;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePart;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\GmailController;
use Google\Service\Gmail\MessagePartBody;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class EmailReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $datas;
    private $client;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->datas = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $applicationName = "my projet";
        $this->client = new Client();
        $this->client->setApplicationName($applicationName);
        $this->client->setAuthConfig(storage_path('app/comnovaellieph05.json'));
        $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.modify');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.send');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');


        foreach ($this->datas as $data) {
            if ($data->action === 'reply') {
                $currentTime = now()->format('Y-m-d H:i:s');
                if ($data->sendingTime <= $currentTime) {
                    try {
                        $replysent = $this->messageSentSchedule($data->reply, $data->messageId, $data->user_id);
                        if ($replysent) {
                            $deleteSentEmailId = OtherWork::where('messageId', $data->messageId)->delete();
                            echo ($deleteSentEmailId) ? 'replySentIdDeleteSuccess ---' : 'replySentIdDeleteFailed --- ';
                        } else {
                            $updateSentEmailId = OtherWork::where('messageId', $data->messageId)->update(['status' => 'running']);
                            echo ($updateSentEmailId) ? 'replyNotSentIdUpdateSuccess ---' : 'replyNotSentIdUpdateFailed --- ';
                        }
                    } catch (\Exception $e) {
                        $updateSentEmailId = OtherWork::where('messageId', $data->messageId)->update(['status' => 'running']);
                        echo $e->getMessage();
                    }
                }
            }
        }
    }

    public function messageSentSchedule($replyText, $messageId, $userId)
    {
        $checking = $this->checkAccess($userId);
        if ($checking) {
            // Create Gmail service
            $gmail = new Gmail($this->client);
            try {
                $originalMessage = $gmail->users_messages->get('me', $messageId);
                $threadId = $originalMessage->getThreadId();

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

                // Include original attachments
                $parts = $originalMessage->getPayload()->getParts();
                $attachmentParts = [];

                foreach ($parts as $part) {
                    if ($part->getBody()->getAttachmentId()) {
                        $attachmentId = $part->getBody()->getAttachmentId();
                        $attachment = $gmail->users_messages_attachments->get('me', $messageId, $attachmentId);
                        $attachmentParts[] = [
                            'filename' => $part->getFilename(),
                            'mimeType' => $part->getMimeType(),
                            'data' => $attachment->getData()
                        ];
                    }
                }

                // // Create the full message with parts
                // $rawMessage = "From: $from\r\n";
                // $rawMessage .= "To: $to\r\n";
                // $rawMessage .= "Subject: Re: $subject\r\n";
                // $rawMessage .= "In-Reply-To: $messageIdHeader\r\n";
                // $rawMessage .= "References: $messageIdHeader\r\n";
                // $rawMessage .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";
                // $rawMessage .= "\r\n--boundary\r\n";
                // $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
                // $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                // $rawMessage .= $textPartBody->getData();
                // $rawMessage .= "\r\n--boundary\r\n";
                // $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
                // $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                // $rawMessage .= $htmlPartBody->getData();
                // $rawMessage .= "\r\n--boundary--";

                // Create the full message with parts and attachments
                $rawMessage = "From: $from\r\n";
                $rawMessage .= "To: $to\r\n";
                $rawMessage .= "Subject: Re: $subject\r\n";
                $rawMessage .= "In-Reply-To: $messageIdHeader\r\n";
                $rawMessage .= "References: $messageIdHeader\r\n";
                $rawMessage .= "Content-Type: multipart/mixed; boundary=\"boundary\"\r\n";
                $rawMessage .= "\r\n--boundary\r\n";
                $rawMessage .= "Content-Type: multipart/alternative; boundary=\"alternative_boundary\"\r\n";
                $rawMessage .= "\r\n--alternative_boundary\r\n";
                $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $rawMessage .= $textPartBody->getData();
                $rawMessage .= "\r\n--alternative_boundary\r\n";
                $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
                $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $rawMessage .= $htmlPartBody->getData();
                $rawMessage .= "\r\n--alternative_boundary--\r\n";

                // Add attachments
                foreach ($attachmentParts as $attachment) {
                    $rawMessage .= "--boundary\r\n";
                    $rawMessage .= "Content-Type: {$attachment['mimeType']}; name=\"{$attachment['filename']}\"\r\n";
                    $rawMessage .= "Content-Disposition: attachment; filename=\"{$attachment['filename']}\"\r\n";
                    $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $rawMessage .= $attachment['data'] . "\r\n";
                }

                $rawMessage .= "--boundary--";


                // Encode the message
                $rawMessageEncode = base64_encode($rawMessage);
                $plainRawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessageEncode);

                // Create and send the reply message
                $replyMessage = new Message();
                $replyMessage->setRaw($plainRawMessage);
                $replyMessage->setThreadId($threadId);
                $sentMessage = $gmail->users_messages->send('me', $replyMessage);

                return true;
            } catch (\Google\Service\Exception $e) {
                return false;
            }
        } else {
            return false;
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

    // Header GEt
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
    private function checkAccess($userId)
    {
        $dbgoogleToken = GoogleToken::where('user_id', $userId)->first();
        if (!$dbgoogleToken) {
            return false;
        }

        $tokenData = json_decode($dbgoogleToken->access_token, true);
        $this->client->setAccessToken($tokenData);

        // Check if token has expired
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            $newToken = json_encode($this->client->getAccessToken());
            GoogleToken::where('user_id', $userId)->update(['access_token' => $newToken]);
            $tokenData = $this->client->getAccessToken(); // Update tokenData after refresh
        }
        return true;
    }
}
