<?php

namespace App\Jobs;

use Google\Client;
use Google\Service\Gmail;
use App\Models\MailSender;
use App\Models\GoogleToken;
use Google_Service_Exception;
use Illuminate\Bus\Queueable;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class MailSenderJob implements ShouldQueue
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
        $this->client->setAuthConfig(storage_path('app/comshafikul.json'));
        $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.modify');
        $this->client->addScope('https://www.googleapis.com/auth/gmail.send');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
// return Log::info('debag data' . $this->datas);
        foreach ($this->datas as $data) {
            $accessCheck = $this->checkAccess($data->user_id);
            if ($accessCheck) {
                // return Log::info('debag check'. $data);
                $encodeEmail = $this->createEmailWithAttachments($data->client_email, $data->subject, $data->email_content, $data->attachmentPaths, $data->name, $data->email);
                try {
                    $service = new Gmail($this->client);
                    $message = new Message();
                    $message->setRaw($encodeEmail);
                    $service->users_messages->send('me', $message);

                    MailSender::where('id', $data->id)->delete();
                    echo "success -- ";
                } catch (Google_Service_Exception $e) {
                    Log::error('Error sending email: ' . $e->getMessage());
                } catch (\Exception $s) {
                    Log::error('Genaral Error:' . $s->getMessage());
                }
            } else {
                Log::warning('Access check failed for user ID: ' . $data->user_id);
            }
        }
    }

    private function createEmailWithAttachments($client_mail, $subject, $messageText, $attachmentPaths, $name, $email)
    {
        $boundary = uniqid(rand(), true);
        $subject = "=?utf-8?B?" . base64_encode($subject) . "?=";
        $from = "=?utf-8?B?" . base64_encode($name) . "?= <{$email}>";

        $rawMessage = "From: {$from}\r\n";
        $rawMessage .= "To: {$client_mail}\r\n";
        $rawMessage .= "Subject: {$subject}\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: multipart/related; boundary=\"{$boundary}\"\r\n\r\n";

        // Plain text message
        $rawMessage .= "--{$boundary}\r\n";
        $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $rawMessage .= "{$messageText}\r\n";

        foreach ($attachmentPaths as $path) {
            $filePath = storage_path("app/public/{$path}");
            if (!file_exists($filePath)) {
                continue; // Skip if the file doesn't exist
            }
            $fileName = basename($filePath);
            $fileData = file_get_contents($filePath);
            $base64File = base64_encode($fileData);
            $mimeType = mime_content_type($filePath);

            // Attachment
            $rawMessage .= "--{$boundary}\r\n";
            $rawMessage .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
            $rawMessage .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
            $rawMessage .= "Content-ID: <{$fileName}>\r\n";
            $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $rawMessage .= chunk_split($base64File, 76, "\r\n");
        }

        $rawMessage .= "--{$boundary}--";
// return $client_mail;
        return rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
    }


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
