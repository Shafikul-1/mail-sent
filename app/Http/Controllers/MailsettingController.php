<?php

namespace App\Http\Controllers;

use App\Models\Mailsetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use function PHPUnit\Framework\isEmpty;

class MailsettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userAllMail = Mailsetting::where('user_id', Auth::user()->id)->paginate(10);
        return view('mail.userAllMail', compact('userAllMail'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('mail.mailSetting');

        // Mailsetting::create([
        //     'mail_transport' => 'smtp',
        //     'mail_host' => 'smtp.gmail.com',
        //     'mail_port' => '465',
        //     'mail_username' => 'shafikul.18288@gmail.com',
        //     'mail_password' => 'vyoslfufqlszefgv',
        //     'mail_encryption' => 'tls',
        //     'mail_from' => 'shafikul.18288@gmail.com',
        // ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'mail_transport' => 'required',
            'mail_host' => 'required',
            'mail_port' => 'required',
            'mail_username' => 'required',
            'mail_password' => 'required',
            'mail_encryption' => 'required',
            'mail_from' => 'required',
            'mail_sender_name' => 'required',
        ]);

        $userId = Auth::user();
        $mailSetting = Mailsetting::create([
            'mail_transport' => $request->mail_transport,
            'mail_host' => $request->mail_host,
            'mail_port' => $request->mail_port,
            'mail_username' => $request->mail_username,
            'mail_password' => $request->mail_password,
            'mail_encryption' => $request->mail_encryption,
            'mail_from' => $request->mail_from,
            'mail_sender_name' => $request->mail_sender_name,
            'user_id' => $userId->id
        ]);

        if ($mailSetting) {
            return redirect()->route('mailsetting.index')->with('msg', "Your Mail Addded Successful");
        } else {
            return redirect()->back()->with('msg', "Someting Want Wrong");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $editMailSettingData = Mailsetting::find($id);
        return view('mail.editMailSetting', compact('editMailSettingData'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'mail_transport' => 'required',
            'mail_host' => 'required',
            'mail_port' => 'required',
            'mail_username' => 'required',
            'mail_password' => 'required',
            'mail_encryption' => 'required',
            'mail_from' => 'required',
            'mail_sender_name' => 'required',
        ]);

        $updateMailSetting = Mailsetting::where('id', $id)->update([
            'mail_transport' => $request->mail_transport,
            'mail_host' => $request->mail_host,
            'mail_port' => $request->mail_port,
            'mail_username' => $request->mail_username,
            'mail_password' => $request->mail_password,
            'mail_encryption' => $request->mail_encryption,
            'mail_from' => $request->mail_from,
            'mail_sender_name' => $request->mail_sender_name,
        ]);
        if ($updateMailSetting) {
            return redirect()->route('mailsetting.index')->with('msg', "Your Mail Update Successful");
        } else {
            return redirect()->back()->with('msg', "Someting Want Wrong");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleteItem = Mailsetting::find($id)->delete();
        if ($deleteItem) {
            return redirect()->route("mailsetting.index")->with('msg', "Delete Successful");
        } else {
            return redirect()->back()->with('msg', "Someting Want Wrong");
        }
    }
}
