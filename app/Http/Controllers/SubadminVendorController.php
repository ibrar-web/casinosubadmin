<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Arr;
use DateTime;
use Symfony\Component\CssSelector\Node\FunctionNode;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SubadminVendorController extends Controller
{
    public function vendorslist()
    {
        $sequence = auth()->user()->id;
        $users = DB::table('subadminvendors')->where('subadminid', $sequence)->pluck('vendorid');
        $vendors['data'] = DB::table('users')->whereIn('id', $users)->where('role', 'vendor')->orderBy('id', 'DESC')->get()->toArray();
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
        return response($vendors);
    }
    public function vendorsregister(Request $request)
    {
        $adminid = auth()->user()->id;
        $sequence = $request->input('sequence');
        $data = $request->input('data');
        $type = $data['type'];
        switch ($type) {
            case 'create':
                $user = DB::table('users')->where('username', $data['username'])->pluck('id');
                if (count($user) > 0) {
                    $message['err'] = 'Vendor Name already exist';
                    return response($message);
                }
                $id = DB::table('users')->insertGetId([
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'role' => 'vendor',
                    'password' => Hash::make($data['password']),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                //use for vendor account
                Schema::create($id . '_transctionhistory', function (Blueprint $table) {
                    $table->id();
                    $table->string('account');
                    $table->string('amount');
                    $table->string('description');
                    $table->string('from');
                    $table->string('to');
                    $table->string('frombalance');
                    $table->string('tobalance');
                    $table->timestamps();
                });
                //use for users account main
                Schema::create($id . '_accounthistory', function (Blueprint $table) {
                    $table->id();
                    $table->string('account');
                    $table->string('name');
                    $table->string('amount');
                    $table->string('bounce')->default(0);
                    $table->string('description');
                    $table->string('frombalance');
                    $table->string('tobalance');
                    $table->string('color')->default(4);
                    $table->timestamps();
                });
                //for vendor to user deposit redeem profit loss
                Schema::create($id . '_accountreport', function (Blueprint $table) {
                    $table->id();
                    $table->string('account');
                    $table->string('deposit');
                    $table->string('Bounceback');
                    $table->string('Redeems')->default(0);
                    $table->string('profit')->default(0);
                    $table->string('payout')->default(0);
                    $table->timestamps();
                });
                DB::table('subadminvendors')->insert([
                    'vendorid' => $id,
                    'subadminid' => $adminid,
                ]);
                $message['err'] = 'Vendor Created';
                return response($message['err']);
                break;
            case 'edit':
                DB::table('users')->where('id', $sequence)->update([
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                ]);
                $message['err'] = 'Account Updated';
                return response($message['err']);
                break;
            case 'pass':
                DB::table('users')->where('id', $sequence)->update(['password' => Hash::make($data['password']), 'updated_at' => now()]);
                $message['err'] = 'Password changed ';
                return response($message['err']);
                break;
            case 'delete':
                DB::table('users')->where('id', $sequence)->delete();
                Schema::dropIfExists($sequence . '_transctionhistory');
                Schema::dropIfExists($sequence . '_accountreport');
                Schema::dropIfExists($sequence . '_accounthistory');
                $userid = DB::table('vendorsuser')->where('vendorid', $sequence)->pluck('userid');
                DB::table('users')->whereIn('id', $userid)->delete();
                $message['err'] = 'Vendor deleted';
                return response($message['err']);
                break;
            case 'balance':
                $sbadminbalance=DB::table('users')->where('id', $adminid)->pluck('amount')[0];
                if($data['balance']>$sbadminbalance){
                    $message['err'] = 'Please Recharge your account ';
                    return response($message['err']);
                }
                $amount = DB::table('users')->where('id', $sequence)->pluck('amount')[0];
                $startamount = DB::table('users')->where('id', $sequence)->pluck('startamount')[0];
                $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                $previous = $amount;
                $amount = $amount + $data['balance'];
                $startamount = $startamount + $data['balance'];
                $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                DB::table('users')->where('id', $sequence)->update(
                    ['amount' => $amount, 'startamount' => $startamount, 'revert' => true, 'revertamount' => $data['balance']],
                );

                $adminamount = DB::table('users')->where('id', $adminid)->pluck('amount')[0];
                DB::table('users')->where('id', $adminid)->update(
                    ['amount' => $adminamount - $data['balance']],
                );
                DB::table($sequence . '_transctionhistory')->insert([
                    'account' => $username, 'amount' => $data['balance'], 'description' => 'deposit', 'from' => 'admin', 'to' => $name,
                    'frombalance' => $previous, 'tobalance' => $amount, 'created_at' => now(), 'updated_at' => now()
                ]);
                $message['err'] = 'Credit Added';
                return response($message['err']);
                break;
            case 'withdrawal':
                $amount = DB::table('users')->where('id', $sequence)->pluck('amount')[0];
                if ($data['balance'] > $amount) {
                    $message['done'] = 'User do not have enough balance';
                    return response($message['done']);
                }
                $previous = $amount;
                $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                $amount = $amount - $data['balance'];
                DB::table('users')->where('id', $sequence)->update(
                    ['amount' => $amount, 'revert' => false, 'revertamount' => 0],
                );
                $adminamount = DB::table('users')->where('id', $adminid)->pluck('amount')[0];
                DB::table('users')->where('id', $adminid)->update(
                    ['amount' => $adminamount + $data['balance']],
                );
                $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                DB::table($sequence . '_transctionhistory')->insert([
                    'account' => $username, 'amount' => $data['balance'], 'description' => 'withdrawal', 'from' => $name, 'to' => 'admin',
                    'frombalance' => $previous, 'tobalance' =>  $amount, 'created_at' => now(), 'updated_at' => now()
                ]);
                $message['err'] = 'User Account Withdrawal ' . $data['balance'];
                return response($message['err']);
                break;
            case 'rbalance':
                $reward = DB::table('users')->where('id', $sequence)->pluck('reward')[0];
                $previous = $reward;
                $revertamount = DB::table('users')->where('id', $sequence)->pluck('revertamount')[0];
                $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                $reward = $reward - $data['balance'];
                DB::table('users')->where('id', $sequence)->update(
                    ['reward' => $reward, 'revert' => false, 'revertamount' => 0],
                );
                $adminreward = DB::table('users')->where('id', $adminid)->pluck('reward')[0];
                DB::table('users')->where('id', $adminid)->update(
                    ['reward' => $adminreward + $data['balance']],
                );
                $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                DB::table($sequence . '_transctionhistory')->insert([
                    'account' => $username, 'amount' => $data['balance'], 'description' => 'redeem', 'from' => $name, 'to' => 'admin',
                    'frombalance' => $previous, 'tobalance' =>  $reward, 'created_at' => now(), 'updated_at' => now()
                ]);
                $message['done'] = 'User Account Redeem ' . $data['balance'];
                return response($message['done']);
                break;
            case 'clear':
                $amount = DB::table('users')->where('id', $sequence)->pluck('amount')[0];
                $reward = DB::table('users')->where('id', $sequence)->pluck('reward')[0];
                $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                $amount = $amount - $data['balance'];
                DB::table('users')->where('id', $sequence)->update(
                    ['reward' => 0, 'revert' => false, 'revertamount' => 0, 'amount' => 0, 'bounceback' => 0],
                );
                $adminamount = DB::table('users')->where('id', $adminid)->pluck('amount')[0];
                $adminreward = DB::table('users')->where('id', $adminid)->pluck('reward')[0];
                DB::table('users')->where('id', $adminid)->update(
                    ['amount' => $adminamount - $amount, 'reward' => $adminreward + $reward],
                );
                $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                DB::table($sequence . '_transctionhistory')->insert([
                    'account' => $username, 'amount' => $amount, 'description' => 'withdrawal', 'from' => $name, 'to' => 'admin',
                    'frombalance' => $amount, 'tobalance' =>  0, 'created_at' => now(), 'updated_at' => now()
                ]);
                DB::table($sequence . '_transctionhistory')->insert([
                    'account' => $username, 'amount' => $reward, 'description' => 'redeem', 'from' => $name, 'to' => 'admin',
                    'frombalance' => $reward, 'tobalance' =>  0, 'created_at' => now(), 'updated_at' => now()
                ]);
                $message['done'] = 'User Account Cleared';
                return response($message['done']);
                break;
            case 'bonus':
                DB::table('users')->where('id', $sequence)->update(
                    ['bounceback' => $data['balance']],
                );
                $message['done'] = 'Bonus Updated';
                return response($message['done']);
                break;
        }
        return 'dsasd';
    }
    public function transctionhistory(Request $request)
    {
        $vendorid = $request->input('sequence');
        $subadminid=auth()->user()->id;
        $data['data'] = DB::table($vendorid.'_transctionhistory')->orderBy('id', 'DESC')->get();
        $data['c'] = DB::table('users')->where('id', $subadminid)->get();
        return response($data);
    }
}
