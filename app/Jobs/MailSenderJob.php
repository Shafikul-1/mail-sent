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

        foreach ($this->datas as $data) {
            $accessCheck = $this->checkAccess($data->user_id);
            if ($accessCheck) {
                try {
                    $service = new Gmail($this->client);
                    $message = new Message();
                    $message->setRaw($data->email_content);
                    $service->users_messages->send('me', $message);

                    MailSender::where('id', $data->id)->delete();
                    echo "success -- ";
                } catch (Google_Service_Exception $e) {
                    Log::error('Error sending email: ' . $e->getMessage());
                } catch (\Exception $s) {
                    Log::error('Genaral Error:' .$s->getMessage());
                }
            } else {
                Log::warning('Access check failed for user ID: ' . $data->user_id);
            }
        }
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
