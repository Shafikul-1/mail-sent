<?php

namespace App\Http\Controllers;

use App\Models\Mail_message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Mail_messageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sendAllMail = Mail_message::where('user_id', Auth::user()->id)->paginate(10);
        return view('mail.sendMail', compact('sendAllMail'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleteItem = Mail_message::find($id)->delete();
        if($deleteItem){
            return redirect()->route("mail-message.index")->with('msg', "Delete Successful");
        }else{
            return redirect()->back()->with('msg', "Someting Want Wrong");
        }
    }
}
