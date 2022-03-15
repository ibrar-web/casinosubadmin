<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Providers\RouteServiceProvider;
use Exception;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function login(Request $request)
    {
        $input = $request->all();
        Log::info($input);
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required',
            // 'g-recaptcha-response' => 'required|recaptcha'
        ]);
        $fieldType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (auth()->attempt(array($fieldType => $input['username'], 'password' => $input['password']))) {
            $user = Auth::user()->id;
            $confirmation = DB::table('users')
                ->select('role')
                ->where('id', $user)
                ->get()
                ->toArray();
            $confirmation = array_map(function ($value) {
                return (array)$value;
            },   $confirmation);
            if ($confirmation[0]['role'] == 'subadmin') {
                return redirect()->route('home');
            }
            Auth::logout();
            return redirect()->back();
        } else {
            Log::info('not matched');
            return redirect()->back()
                ->with('error', 'Email-Address And Password Are Wrong.');
        }
    }
}
