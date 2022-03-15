<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class VendorController extends Controller
{
    public function userslist()
    {
        try {
            $sequence = auth()->user()->id;
            $users = DB::table('vendorsuser')->where('vendorid', $sequence)->pluck('userid');
            $vendors['data'] = DB::table('users')->whereIn('id', $users)->where('role', 'user')->where('dispute', false)->orderBy('id', 'DESC')->get()->toArray();
            $vendors['data'] = array_map(function ($value) {
                return (array)$value;
            }, $vendors['data']);
            for ($i = 0; $i < count($vendors['data']); $i++) {
                if (Cache::has('user-is-online-' . $vendors['data'][$i]['id']))
                    $vendors['data'][$i]['status'] = 'Online';
                else
                    $vendors['data'][$i]['status'] = 'Offline';
            }
            $id =  DB::table('users')->first();
            $vendors['sequence'] = $id->id + 1;
            $vendors['c'] = DB::table('users')->where('id', $sequence)->get();
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return response($vendors);
    }
}
