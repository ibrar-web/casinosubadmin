<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $role=DB::table('users')->where('id',Auth::user()->id)->pluck('role')[0];
        Log::info($role);
        if($role=='subadmin'){
          return view('SubadminHome');
        }else{
          Auth::logout();
          return abort(500,'user ');
        }
    }
}
