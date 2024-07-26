<?php

namespace App\Http\Controllers;

use App\Jobs\EmailReplyJob;
use DateTime;
use App\Models\OtherWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtherWorkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentDate = now();
        $nextTime = $currentDate->addMinutes(10)->format('Y-m-d H:i:s');
        $datas = OtherWork::where('sendingTime' , '<=', $nextTime)->get(['id','action', 'messageId', 'sendingTime', 'user_id', 'reply']);
        // return $datas;
       
        try{
            EmailReplyJob::dispatch($datas);
            echo "success --- ";
        } catch(\Throwable $error){
            echo $error->getMessage();
        }
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

    // sent all mail thake all data asche
    public function multiWork(Request $request)
    {
        $userId = Auth::user()->id;
        $request->validate([
            'messageId' => 'required|array',
            'action' => 'required|string',
        ]);

        // if (!is_null($reqest->sendingTime)) {
        //     $request->validate([
        //         'sendingTime' => 'numeric',

        //     ]);
        // }

        $intervalMinutes = $request->sendingTime;
        foreach ($request->messageId as $id) {
            $currentTime = now(); // or use \Carbon\Carbon::now()
            $scheduleTime = $currentTime->addMinutes($intervalMinutes)->format('Y-m-d H:i:s');
            $workData = OtherWork::create([
                'action' => $request->action,
                'messageId' => $id,
                'user_id' => $userId,
                'reply' => $request->reply,
                'sendingTime' => $scheduleTime,
            ]);
            $intervalMinutes += $request->sendingTime;
        }
        return redirect()->back()->with('msg', "Success Other Worked");
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
        //
    }
}
