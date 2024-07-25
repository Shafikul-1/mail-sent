<?php

namespace App\Http\Controllers;

use DateTime;
use DateInterval;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;


class MailfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $data = User::where('email', 'shafikul@gmail.com')->count();
        // $currentTime = Carbon::now();
        // $mytime = Carbon::now();
        // return $mytime->toArray();
        // $currentTime = $mytime->toDateTimeString();
        $currentTime = new DateTime();
        $scheduleInterval = 10; // Interval of 10 minutes
        
        echo "Current time: " . $currentTime->format("l jS \of F Y h:i:s A") . "<br>";
        
        $arr = [
            'fast' => 'fast',
            'second' => 'second',
            'third' => 'third', // Fixed typo: 'thred' to 'third'
            'six' => 'six',
            'seven' => 'seven',
        ];
        
        $currentTime = new DateTime();
        foreach($arr as $key => $id){
            $scheduleTime = clone $currentTime; // Clone the current time to avoid modifying the original
            $scheduleTime->add(new DateInterval('PT' . (((int)$key + 1) * $scheduleInterval) . 'M'));
            echo $scheduleTime->format("l jS \of F Y h:i:s A") . "<br>";

        }
        // return view('check');
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
        //
    }
}
