<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use Config;
use App\Mail\testMail;
use App\Models\ClientMail;
use App\Models\Mail_message;
use App\Models\Mailsetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use function PHPUnit\Framework\isEmpty;

class testController extends Controller
{
    public function sentEmail()
    {
        // ini_set('max_execution_time', 300);
        $allusers = User::all('id');
        foreach ($allusers as $userId) {
            $mailSettings = Mailsetting::where('user_id', $userId->id)->limit(10)->get();
            if ($mailSettings->isEmpty()) {
                // echo "empty user id = " . $userId->id;
                continue;
            }
            try {
                SendEmailJob::dispatch($mailSettings);
                echo "success -- <br>";
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
    }
}
            // foreach ($mailSettings as $mailSettingData) {
            // }
            // echo "<div style='border:3px solid green'>";
            // // echo $mailSettingData->id . "<br>";
            // // echo $mailSettingData->mail_transport . "<br>";
            // // echo $mailSettingData->mail_host . "<br>";
            // // echo $mailSettingData->mail_port . "<br>";
            // // echo $mailSettingData->mail_username . "<br>";
            // // echo $mailSettingData->mail_encryption . "<br>";
            // // echo $mailSettingData->mail_from . "<br>";
            // // echo $mailSettingData->mail_sender_name . "<br>";

            // echo "user id = " . $mailSettingData->user_id . "<br>";

            // $senderData = ClientMail::where('user_id', $mailSettingData->user_id)->first();
            // if(!is_null($senderData)){
            //     echo "First Data = " . $senderData->id;
            // }else{
            //     echo "ClientMail Table id not found --> " . $mailSettingData->user_id;
            // }
            // echo "</div>";

        // Iterate over each mail setting
        // foreach ($mailSettings as $mailSetting) {
        //     // // Configure the mail settings
        //     // $data = [
        //     //     'driver' => $mailSetting->mail_transport,
        //     //     'host' => $mailSetting->mail_host,
        //     //     'port' => $mailSetting->mail_port,
        //     //     'encryption' => $mailSetting->mail_encryption,
        //     //     'username' => $mailSetting->mail_username,
        //     //     'password' => $mailSetting->mail_password,
        //     //     'from' => [
        //     //         'address' => $mailSetting->mail_from,
        //     //         'name' => $mailSetting->mail_sender_name,
        //     //     ],
        //     // ];
        //     // Config::set('mail', $data);
        //     // Mail::purge();
        //     // // Send an email to the client using the current mail setting

        //     // $senderData = ClientMail::first();
        //     // try {
        //     //     // Mail::to($senderData->mail)->send(new testMail($senderData));
        //     //     $mailStatus = true;
        //     // } catch (\Throwable $th) {
        //     //     echo $th->getMessage();
        //     //     Log::error('Failed to send email with settings: ' . json_encode($mailSetting) . ', Error: ' . $th->getMessage());
        //     //     $mailStatus = false;
        //     // }
        //     // $mailMessage = Mail_message::create([
        //     //     'sender_mail' => $mailSetting->mail_username,
        //     //     'reciver_mail' => $senderData->mail,
        //     //     'mail_status' => $mailStatus,
        //     //     'msg' => ($mailStatus) ? 'Mail sent Succesful' : 'Mail sent failed'
        //     // ]);

        //     // $senderMailDelete = $senderData->delete();
        //     echo $mailSetting->mail_username . ', ==';
        // echo $mailStatus . ' <br>';


        // }



    // public function sentEmail()
    // {
    //     $mailSetting = Mailsetting::all();
    //     foreach ($mailSetting as $key => $value) {
    //         $data = [
    //             'driver' => $value->mail_transport,
    //             'host' => $value->mail_host,
    //             'port' => $value->mail_port,
    //             'encryption' => $value->mail_encryption,
    //             'username' => $value->mail_username,
    //             'password' => $value->mail_password,
    //             'from' => [
    //                 'address' => $value->mail_from,
    //                 'name' => $value->mail_sender_name,
    //             ],
    //         ];
    //         Config::set('mail', $data);


    //         $senderData = ClientMail::first();
    //         Mail::to($senderData->mail)->send(new testMail($senderData));
    //     }
    // }
