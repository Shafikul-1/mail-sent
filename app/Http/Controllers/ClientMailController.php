<?php

namespace App\Http\Controllers;

use Log;
use App\Models\ClientMail;
use App\Models\Sender_mail;
use App\Models\Mail_message;
use App\Models\Mailfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class ClientMailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Auth::user();
        // $SenderMails = Sender_mail::all();
        // $clientMail = ClientMail::first();
        // if ($clientMail) {
        //     foreach ($SenderMails as $value) {
        //         $mail_arr = [
        //             'mail' => $clientMail->mail,
        //             'mail_subject' => $clientMail->mail_subject,
        //             'mail_body' => $clientMail->mail_body,
        //             'mail_files' => $clientMail->mail_files,
        //             'from_email' => $value['mail'],
        //         ];
        //         $messageMail = Mail_message::create([
        //             'mail' => $clientMail->mail,
        //             'msg' => 'mail sent successful'
        //         ]);
        //         $deleteMail = ClientMail::where('mail', $clientMail->mail)->delete();

        //         echo "<pre>";
        //         print_r($mail_arr);
        //         echo " </pre>";
        //     }
        // }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $mailFiles = Mailfile::select('all_files_name')->where('user_id', Auth::user()->id)->get();
        $allFiles = "";
        foreach ($mailFiles as $files) {
            $allFiles .= $files['all_files_name'] . ",";
        }
        $filesAll = explode(',', $allFiles);
        // return $filesAll;
        return view('mail.multiMailForm')->with('allFiles', $filesAll);
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
            'mail_all' => 'required',
            'mail_subject' => 'required',
            'mail_body' => 'required',
            // 'mail_files' =>'required|max:50000|mimes:xlsx,doc,docx,ppt,pptx,ods,odt,odp,application/csv,application/excel',
        ]);

        $userId = Auth::user();
        $all_files = array();
        if ($request->file('mail_files')) {
            foreach ($request->file('mail_files') as $key => $value) {
                $originalName = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $value->getClientOriginalExtension();
                $path = $value->storeAs('upload', $originalName, 'public');
                array_push($all_files, $path);
            }
        }

        // mail alll file name mail_files table added
        $allFileName = implode(',', $all_files);
        if($allFileName !== ""){
            Mailfile::create([
                'all_files_name' => $allFileName,
                'user_id' => $userId->id,
            ]);
        } 
        
        // Check file upload or select previse file
        if(!is_null($request->mail_previse_file)){
            if($allFileName == ""){
                $allFileName =  $request->mail_previse_file;
            } else{
                $allFileName .= "," . $request->mail_previse_file;
            }
        }

        // All Mail create array and loop
        $allMails = preg_split('/\s+/', $request->mail_all);
        foreach ($allMails as $allMail) {
            $storeDB = ClientMail::create([
                'mail' => $allMail,
                'mail_subject' => $request->mail_subject,
                'mail_body' => $request->mail_body,
                'mail_files' => $allFileName,
                'user_id' => $userId->id,
            ]);
        }

        return redirect()->route('unSendMail')->with('msg', 'You have successfully File upload & All Mail.');
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
        $editData = ClientMail::find($id);
        return view('mail.editUnsendEmail', compact('editData'));
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
        //
    }

    // Send All Mail
    // public function sendMail()
    // {
    //     $sendAllMail = Mail_message::where('user_id', Auth::user()->id)->paginate(5);
    //     return view('mail.sendMail', compact('sendAllMail'));
    // }

    // Un Send All Mail
    public function unSendMail()
    {
        $unsendEmail = ClientMail::where('user_id', Auth::user()->id)->paginate(10);
        return view('mail.unsendEmail', compact('unsendEmail'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleteItem = ClientMail::find($id)->delete();
        if($deleteItem){
            return redirect()->route("unSendMail")->with('msg', "Delete Successful");
        }else{
            return redirect()->back()->with('msg', "Someting Want Wrong");
        }
    }
}
