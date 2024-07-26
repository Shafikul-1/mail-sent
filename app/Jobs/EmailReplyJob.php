<?php

namespace App\Jobs;

use Google\Client;
use App\Models\User;
use Google\Service\Gmail;
use App\Models\GoogleToken;
use Illuminate\Bus\Queueable;
use Google\Service\Gmail\Message;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\GmailController;
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
        $this->client->setAuthConfig(storage_path('app/client_secrate.json'));
        $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.modify');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.send');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        foreach($this->datas as $data){
            if($data->action === 'reply'){
                $replysent = $this->messageSentSchedule($data->reply, $data->messageId, $data->user_id);
                if($replysent){
                    echo "successful reply -- ";
                } else{
                    echo "failed";
                }
            }
        }
    }

    
    public function messageSentSchedule($replyText, $messageId, $userId)
    {
        // $returnData = $replyText . '  ---  ' . $messageId;
        // return $returnData;

        $checking = $this->checkAccess($userId);
        if ($checking) {
            // Create Gmail service
            $gmail = new Gmail($this->client);
            try {
                $originalMessage = $gmail->users_messages->get('me', $messageId);
                $threadId = $originalMessage->getThreadId();

                // Create the raw MIME message
                $replyMessage = new Message();
                $rawMessage = "From: " . $this->getHeader($originalMessage, 'From') . "\r\n";
                $rawMessage .= "To: " . $this->getHeader($originalMessage, 'To') . "\r\n";
                $rawMessage .= "Subject: Re: " . $this->getHeader($originalMessage, 'Subject') . "\r\n";
                $rawMessage .= "In-Reply-To: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "References: " . $this->getHeader($originalMessage, 'Message-ID') . "\r\n";
                $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
                $rawMessage .= "Content-Transfer-Encoding: quoted-printable\r\n";
                $rawMessage .= "\r\n " . $replyText;
                // return $rawMessage;
                $rawMessageEncode = base64_encode($rawMessage);
                $plainRawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessageEncode);

                $replyMessage->setRaw($plainRawMessage);
                $replyMessage->setThreadId($threadId);
                $sentMessage = $gmail->users_messages->send('me', $replyMessage);

                return response()->json(['message' => 'Reply sent successfully']);
            } catch (\Google\Service\Exception $e) {
                return $e->getMessage();
            }
        } else {
            return redirect()->route('home')->with('msg', "Token Expired");
        }
    }

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
            return redirect()->route('home')->with('msg', "Token is null");
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
