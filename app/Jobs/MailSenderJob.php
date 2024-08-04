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

    public $tries = 5; // Set the maximum number of attempts
    public $timeout = 300; // Set the timeout in seconds (e.g., 300 seconds = 5 minutes)
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
        ini_set('memory_limit', '256M');
        $startTime = microtime(true);
        Log::info('Job started at ' . $startTime);

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
                $encodeEmail = $this->createEmailWithAttachments($data->client_email, $data->subject, $data->email_content, $data->attachmentPaths, $data->name, $data->email);
                try {
                    $service = new Gmail($this->client);
                    $message = new Message();
                    $message->setRaw($encodeEmail);
                    $service->users_messages->send('me', $message);

                    MailSender::where('id', $data->id)->delete();
                    echo "success -- ";
                } catch (Google_Service_Exception $e) {
                    MailSender::where('id', $data->id)->update(['status' => 0]);
                    Log::error('Error sending email: ' . $e->getMessage());
                } catch (\Exception $s) {
                    MailSender::where('id', $data->id)->update(['status' => 0]);
                    Log::error('Genaral Error:' . $s->getMessage());
                }
            } else {
                Log::warning('Access check failed for user ID: ' . $data->user_id);
            }
        }

        $endTime = microtime(true);
        Log::info('Job ended at ' . $endTime);
        Log::info('Job duration: ' . ($endTime - $startTime) . ' seconds');
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
            // if (!file_exists($filePath)) {
            //     continue; // Skip if the file doesn't exist
            // }
            // $fileName = basename($filePath);
            // $fileData = file_get_contents($filePath);
            // $base64File = base64_encode($fileData);
            // $mimeType = mime_content_type($filePath);
            // $rawMessage .= "--{$boundary}\r\n";

            // Attachment
            // $rawMessage .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
            // $rawMessage .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
            // $rawMessage .= "Content-ID: <{$fileName}>\r\n";
            // $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
            // $rawMessage .= chunk_split($base64File, 76, "\r\n");


            if (!$this->isFileFullyUploaded($filePath)) {
                continue; // Skip if the file is not fully uploaded
            }
            $fileName = basename($filePath);
            $mimeType = mime_content_type($filePath);

            // Attachment
            $rawMessage .= "--{$boundary}\r\n";
            $rawMessage .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
            $rawMessage .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
            $rawMessage .= "Content-ID: <{$fileName}>\r\n";
            $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";

            // Read file in chunks
            $handle = fopen($filePath, "rb");
            while (!feof($handle)) {
                $chunk = fread($handle, 10 * 1024 * 1024); // Read 10M at a time
                $base64Chunk = base64_encode($chunk);
                $rawMessage .= chunk_split($base64Chunk, 76, "\r\n");
            }
            fclose($handle);
        }

        $rawMessage .= "--{$boundary}--";

        // return $client_mail;
        return rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
    }

    private function isFileFullyUploaded($filePath)
    {
        $retries = 10;
        $waitTime = 3; // seconds

        for ($i = 0; $i < $retries; $i++) {
            if (file_exists($filePath)) {
                return true;
            }
            sleep($waitTime);
        }
        return false;
    }

    private function checkAccess($userId)
    {
        $dbgoogleToken = GoogleToken::where('user_id', $userId)->first();
        if (!$dbgoogleToken) {
            return false;
        }
        try {
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
        } catch (\Exception $rmT) {
            Log::info('Access Token Expaire' . $rmT->getMessage());
        }
    }
}
