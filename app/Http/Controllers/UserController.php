<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // if(!Gate::allows('isAdmin')){
        //     return redirect()->route('login');
        // }
        Gate::authorize('isAdmin');
        $users = User::all();
        return view('users.allusers', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.signup');
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phonenumber' => 'required|numeric|digits:11',
            'password' => 'required|min:6|confirmed',
        ]);
        $setUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'password' => $request->password,
            'role' => $request->role,
        ]);
        if ($setUser) {
            return redirect()->route('mailsetting.index')->with('msg', 'User Create Successful');
        }
    }

    // User Login View
    public function login()
    {
        if(Auth::check()){
            return redirect()->back();
        }
        return view('users.login');
    }

    // Login Logic
    public function checkUser(Request $request)
    {
        $credentional = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentional)) {
            return redirect()->route('mailsetting.index')->with('msg', 'User Logged In Successful');
        } else {
            return redirect()->back()->with('msg', 'User name and Password not match');
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
        if (Gate::allows('currentUser', intval($id))) {
            $singleUser = User::find($id);
            return view('users.profile', compact('singleUser'));
        } else {
            abort(403);
        }
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

    public function logout()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        Auth::logout();
        return redirect()->route('home')->with('msg', "Log out successful");
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
