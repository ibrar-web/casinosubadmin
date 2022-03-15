<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class VendorHomeController extends Controller
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
                if ($vendors['data'][$i]['last_seen'] > now()) {
                    $vendors['data'][$i]['status'] = 'Online';
                } else {
                    $vendors['data'][$i]['status'] = 'Offline';
                }
            }
            $id =  DB::table('users')->first();
            $vendors['sequence'] = $id->id + 1;
            $vendors['c'] = DB::table('users')->where('id', $sequence)->get();
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return response($vendors);
    }
    public function usersregister(Request $request)
    {
        try {
            $vendorid = auth()->user()->id;
            $sequence = $request->input('sequence');
            $data = $request->input('data');
            $type = $data['type'];
            switch ($type) {
                case 'create':
                    $vendorname = DB::table('users')->where('id', $vendorid)->pluck('name')[0];
                    $user = DB::table('users')->where('username', $data['username'])->pluck('id');
                    if (count($user) > 0) {
                        $message['err'] = 'User Name already exist';
                        return response($message['err']);
                    }
                    if ($data['name'] == '') {
                        $message['err'] = 'Please Add user name';
                        return response($message['err']);
                    }
                    $vendoramount = DB::table('users')->where('id', $vendorid)->pluck('amount')[0];
                    if ($vendoramount < $data['balance']) {
                        $message['err'] = 'Please Recharge your Account';
                        return response($message['err']);
                    }
                    $previousvb = $vendoramount;
                    $vendoramount = $vendoramount - $data['balance'];
                    $user = DB::table('users')->pluck('id');
                    $username = rand(10, 100) . $vendorid . rand(10, 100) . count($user) . rand(10, 100)  . rand(10, 100) . rand(10, 100);
                    $username = $username[0] . $username[1] . '-' . $username[2] . $username[3] . '-' . $username[4] . $username[5] . '-' . $username[6] . $username[7] . '-' . $username[8] . $username[9] . '-' . $username[10] . $username[11];
                    $id = DB::table('users')->insertGetId([
                        'name' => $data['name'],
                        'username' => $username,
                        'email' => ' ',
                        'role' => 'user',
                        'password' => Hash::make($username),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    DB::table('vendorsuser')->insert([
                        'vendorid' => $vendorid,
                        'userid' => $id,
                    ]);
                    if ($data['balance'] == '') {
                        $message['err'] = 'User Created ';
                        return response($message['err']);
                        break;
                    }
                    $bounceback = DB::table('users')->where('id', $vendorid)->pluck('bounceback')[0];
                    $balance = $data['balance'];
                    if (!$request->input('bounce')) {
                        $bounceback = 0;
                    }
                    $bounceamount = $balance * ($bounceback / 100);
                    if ($request->input('bounce')) {
                        DB::table('users')->where('id', $id)->update([
                            'amount' => $data['balance'] + $bounceamount, 'revert' => true, 'revertamount' => $data['balance'] + $bounceamount, 'bouncebackdate' => date('Y-m-d', time()),
                            'bounceback' => $bounceamount, 'lastbounce' => $bounceamount
                        ]);
                    } else {
                        DB::table('users')->where('id', $id)->update([
                            'amount' => $data['balance'] + $bounceamount, 'revert' => true, 'revertamount' => $data['balance'] + $bounceamount,
                            'bounceback' => $bounceamount, 'lastbounce' => $bounceamount
                        ]);
                    }
                    DB::table('users')->where('id', $vendorid)->update([
                        'amount' =>  $vendoramount
                    ]);
                    $name = DB::table('users')->where('id', $id)->pluck('name')[0];
                    DB::table($vendorid . '_accounthistory')->insert([
                        'account' => $username, 'amount' => $data['balance'], 'description' => 'deposit', 'name' => $name,
                        'frombalance' => 0, 'bounce' => $bounceamount, 'tobalance' => $data['balance'], 'created_at' => now(), 'updated_at' => now(), 'color' => 4
                    ]);
                    $todayreport = DB::table($vendorid . '_accountreport')->whereDate('created_at', '=', date('Y-m-d', time()))->get()->toArray();
                    $todayreport = array_map(function ($value) {
                        return (array)$value;
                    }, $todayreport);
                    if (count($todayreport) > 0) {
                        Log::info('val');
                        $todayreportid = $todayreport[0]['id'];
                        $deposit = $todayreport[0]['deposit'] + $data['balance'];
                        $Bounceback = $todayreport[0]['Bounceback'] + $bounceamount;
                        $Redeems = $todayreport[0]['Redeems'];
                        $profit = $deposit - $Redeems;
                        $payout = ($Redeems / $profit) * 100;
                        DB::table($vendorid . '_accountreport')->where('id', $todayreportid)->update([
                            'account' => $vendorname, 'deposit' => round($deposit, 2), 'Bounceback' => round($Bounceback, 2),
                            'profit' => round($profit, 2), 'payout' => round($payout, 2), 'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                        ]);
                        $message['err'] = 'User Created ';
                        return response($message['err']);
                    }
                    DB::table($vendorid . '_accountreport')->insert([
                        'account' => $vendorname, 'deposit' => $data['balance'], 'Bounceback' => round($bounceamount, 2),
                        'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                    ]);
                    $message['err'] = 'User Created ';
                    return response($message['err']);
                    break;
                case 'ban':
                    $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                    $vendorname = DB::table('users')->where('id', $vendorid)->pluck('name')[0];
                    $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                    $status = DB::table('users')->where('id', $sequence)->pluck('ban')[0];
                    if ($status == 'active') {
                        DB::table('users')->where('id', $sequence)->update([
                            'ban' => 'ban',
                        ]);
                        $message['err'] = $name . ' will not be able to play games due to ban';
                        return response($message['err']);
                    } else {
                        DB::table('users')->where('id', $sequence)->update([
                            'ban' => 'active',
                        ]);
                        $message['err'] = $name . ' has access to all games';
                        return response($message['err']);
                    }
                    break;
                case 'active':
                    $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                    $vendorname = DB::table('users')->where('id', $vendorid)->pluck('name')[0];
                    $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                    $status = DB::table('users')->where('id', $sequence)->pluck('ban')[0];
                    if ($status == 'active') {
                        DB::table('users')->where('id', $sequence)->update([
                            'ban' => 'ban',
                        ]);
                        $message['err'] = $name . ' will not be able to play games due to ban';
                        return response($message['err']);
                    } else {
                        DB::table('users')->where('id', $sequence)->update([
                            'ban' => 'active',
                        ]);
                        $message['err'] = $name . ' has access to all games';
                        return response($message['err']);
                    }
                    break;
                case 'pass':
                    $username = rand(10, 100) . $vendorid . rand(10, 100) . $sequence . rand(10, 100)  . rand(10, 100) . rand(10, 100);
                    $username = $username[0] . $username[1] . '-' . $username[2] . $username[3] . '-' . $username[4] . $username[5] . '-' . $username[6] . $username[7] . '-' . $username[8] . $username[9] . '-' . $username[10] . $username[11];
                    DB::table('users')->where('id', $sequence)->update([
                        'username' => $username,
                        'password' => Hash::make($username), 'updated_at' => now(),
                        'firstlog' => 'yes'
                    ]);
                    $message['err'] = 'Password Updated';
                    return response($message['err']);
                    break;
                case 'delete':
                    DB::table('users')->where('id', $sequence)->update([
                        'close' => true, 'dispute' => true
                    ]);

                    $message['err'] = 'User account added into disputes';
                    return response($message['err']);
                    $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                    $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                    $amount = DB::table('users')->where('id', $sequence)->pluck('amount')[0];
                    $vendorname = DB::table('users')->where('id', $vendorid)->pluck('name')[0];
                    $previousvb = DB::table('users')->where('id', $vendorid)->pluck('amount')[0];
                    $vendoramount = $previousvb + $amount;
                    DB::table('users')->where('id', $vendorid)->update(
                        ['amount' => $vendoramount]
                    );
                    ///only use from deposit redeem and revert for vendor and user
                    DB::table($vendorid . '_accounthistory')->insert([
                        'account' => $username, 'amount' => $amount, 'description' => 'account delete',
                        'frombalance' => $previousvb, 'tobalance' => $vendoramount, 'created_at' => now(), 'updated_at' => now()
                    ]);
                    $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                    DB::table('users')->where('id', $sequence)->delete();
                    DB::table($vendorid . '_accounthistory')->where('account', $username)->delete();
                    DB::table('vendorsuser')->where('userid', $sequence)->delete();
                    $message['err'] = 'User account and history cleared from system';
                    return response($message['err']);
                    break;
                case 'credit':
                    $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                    $vendorname = DB::table('users')->where('id', $vendorid)->pluck('name')[0];
                    $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                    $vendoramount = DB::table('users')->where('id', $vendorid)->pluck('amount')[0];
                    if ($vendoramount < $data['balance']) {
                        $message['err'] = 'Please Recharge your Account';
                        return response($message['err']);
                    }
                    $bouncebackdate = DB::table('users')->where('id', $sequence)->whereDate('bouncebackdate', '=', date('Y-m-d', time()))->pluck('id');
                    $bounceback = DB::table('users')->where('id', $vendorid)->pluck('bounceback')[0];
                    $userbounceback = DB::table('users')->where('id', $sequence)->pluck('bounceback')[0];
                    if ($request->input('bounce')) {
                        if (count($bouncebackdate) > 0) {
                            $message['err'] = 'User Already Availed Bonus';
                            return response($message['err']);
                        }
                    } else {
                        $bounceback = 0;
                    }
                    $amount = DB::table('users')->where('id', $sequence)->pluck('amount')[0];

                    $previous = $amount;
                    $previousvb = $vendoramount;
                    $vendoramount = $vendoramount - $data['balance'];
                    $balance = $data['balance'];
                    $bounceamount = $balance * ($bounceback / 100);
                    $amount = $amount + $data['balance'] + $bounceamount;
                    $bounceback = $bounceback + $bounceamount;
                    $userbounceback = $userbounceback + $bounceamount;
                    if ($request->input('bounce')) {
                        DB::table('users')->where('id', $sequence)->update(
                            [
                                'amount' => $amount, 'revertamount' => $data['balance'] + $bounceamount, 'bouncebackdate' => date('Y-m-d', time()),
                                'bounceback' => $userbounceback, 'revert' => true, 'lastbounce' => $bounceamount
                            ]
                        );
                    } else {
                        DB::table('users')->where('id', $sequence)->update(
                            [
                                'amount' => $amount, 'revertamount' => $data['balance'], 'revert' => true, 'lastbounce' => 0
                            ]
                        );
                    }
                    DB::table('users')->where('id', $vendorid)->update(
                        ['amount' => $vendoramount],
                    );
                    ///only use from deposit redeem and revert and games of user
                    DB::table($vendorid . '_accounthistory')->insert([
                        'account' => $username, 'amount' => $data['balance'], 'description' => 'deposit', 'name' => $name,
                        'frombalance' => $previous, 'bounce' => $bounceamount, 'tobalance' => $amount, 'created_at' => now(), 'updated_at' => now()
                    ]);
                    $todayreport = DB::table($vendorid . '_accountreport')->whereDate('created_at', '=', date('Y-m-d', time()))->get()->toArray();
                    $todayreport = array_map(function ($value) {
                        return (array)$value;
                    }, $todayreport);
                    if (count($todayreport) > 0) {
                        Log::info('val');
                        $todayreportid = $todayreport[0]['id'];
                        $deposit = $todayreport[0]['deposit'] + $data['balance'];
                        $Bounceback = $todayreport[0]['Bounceback'] + $bounceamount;
                        $Redeems = $todayreport[0]['Redeems'];
                        $profit = $deposit - $Redeems;
                        $payout = 0;
                        if ($Redeems > 0 && $profit > 0) {
                            $payout = ($profit / $Redeems) * 100;
                        }
                        DB::table($vendorid . '_accountreport')->where('id', $todayreportid)->update([
                            'account' => $vendorname, 'deposit' => $deposit, 'Bounceback' => $Bounceback,
                            'profit' => round($profit, 2), 'payout' => round($payout, 2), 'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                        ]);
                        $message['err'] = 'User Accuont Recharged';
                        return response($message['err']);
                    }
                    DB::table($vendorid . '_accountreport')->insert([
                        'account' => $vendorname, 'deposit' => round($data['balance'], 2), 'Bounceback' => round($bounceamount, 2),
                        'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                    ]);
                    $message['err'] = 'Credit Added in User Account';
                    return response($message['err']);
                    break;
                case 'revert':
                    $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                    $vendorname = DB::table('users')->where('id', $vendorid)->pluck('name')[0];
                    $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                    if (!DB::table('users')->where('id', $sequence)->pluck('revert')[0]) {
                        $message['err'] = 'User has not recharged his account';
                        return response($message);
                    }
                    $amount = DB::table('users')->where('id', $sequence)->pluck('amount')[0];
                    $revertamount = DB::table('users')->where('id', $sequence)->pluck('revertamount')[0];
                    $lastbounce = DB::table('users')->where('id', $sequence)->pluck('lastbounce')[0];
                    $totalboucebackuser = DB::table('users')->where('id', $sequence)->pluck('bounceback')[0];
                    $totalbouceback = DB::table('users')->where('id', $vendorid)->pluck('bounceback')[0];
                    $vendoramount = DB::table('users')->where('id', $vendorid)->pluck('amount')[0];
                    $previous = $amount;
                    $amount = $amount - $revertamount;
                    $previousvb = $vendoramount;
                    $vendoramount = $vendoramount + $revertamount - $lastbounce;
                    $todayreport = DB::table($vendorid . '_accountreport')->whereDate('created_at', '=', date('Y-m-d', time()))->get()->toArray();
                    $todayreport = array_map(function ($value) {
                        return (array)$value;
                    }, $todayreport);
                    if (count($todayreport) > 0) {
                        $todayreportid = $todayreport[0]['id'];
                        $deposit = $todayreport[0]['deposit'] - $revertamount + $lastbounce;
                        $Bounceback = $todayreport[0]['Bounceback'] - $lastbounce;
                        $Redeems = $todayreport[0]['Redeems'];
                        $profit = $deposit - $Redeems;
                        $payout = 0;
                        if ($Redeems > 0 && $profit > 0) {
                            $payout = ($profit / $Redeems) * 100;
                        }
                        DB::table($vendorid . '_accountreport')->where('id', $todayreportid)->update([
                            'account' => $vendorname, 'deposit' => $deposit, 'Bounceback' => round($Bounceback, 2),
                            'profit' => round($profit, 2), 'payout' => round($payout, 2), 'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                        ]);
                    } else {
                        DB::table($vendorid . '_accountreport')->insert([
                            'account' => $vendorname, 'deposit' => $data['balance'],
                            'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                        ]);
                    }
                    DB::table('users')->where('id', $sequence)->update(
                        ['amount' => $amount, 'revert' => false, 'lastbounce' => 0, 'bounceback' => $totalboucebackuser - $lastbounce],
                    );
                    DB::table('users')->where('id', $vendorid)->update(
                        ['amount' => $vendoramount, 'lastbounce' => 0],
                    );

                    ///only use from deposit redeem and revert and games of user
                    DB::table($vendorid . '_accounthistory')->insert([
                        'account' => $username, 'amount' => $revertamount, 'bounce' => -$lastbounce, 'description' => 'revert', 'name' => $name,
                        'frombalance' => $previous, 'tobalance' => $amount, 'created_at' => now(), 'updated_at' => now(), 'color' => 4
                    ]);

                    $message['err'] = 'User last recharge reverted back';
                    return response($message['err']);
                    break;
                case 'redeem':
                    $bounceback = DB::table('users')->where('id', $sequence)->pluck('bounceback')[0];
                    $redeem = DB::table('users')->where('id', $sequence)->pluck('reward')[0];
                    if ($data['balance'] > ($bounceback + $redeem)) {
                        $message['err'] = 'User does not have enough redeem and bonus';
                        return response($message['err']);
                    }
                    $username = DB::table('users')->where('id', $sequence)->pluck('username')[0];
                    $vendorname = DB::table('users')->where('id', $vendorid)->pluck('name')[0];
                    $name = DB::table('users')->where('id', $sequence)->pluck('name')[0];
                    if ($bounceback > 0) {
                        $redeemamount = $data['balance'];
                        $bouncebackafter = $bounceback - $data['balance'];
                        if ($bouncebackafter > 0) {
                            DB::table('users')->where('id', $sequence)->update(
                                ['bounceback' => $bounceback - $data['balance'], 'reward' => $redeem - $data['balance']],
                            );
                        } else {
                            DB::table('users')->where('id', $sequence)->update(
                                ['bounceback' => 0],
                            );
                            $data['balance'] = abs($bouncebackafter);
                        }
                        ///only use from deposit redeem and revert and games of user
                        DB::table($vendorid . '_accounthistory')->insert([
                            'account' => $username, 'amount' => $redeemamount, 'description' => 'bouncereturn', 'name' => $name,
                            'frombalance' => $bounceback, 'bounce' => $redeemamount, 'tobalance' => $bouncebackafter, 'created_at' => now(), 'updated_at' => now(), 'color' => 5
                        ]);
                        $bounceback = DB::table('users')->where('id', $sequence)->pluck('bounceback')[0];
                        if ($bounceback > 0) {
                            $message['err'] = 'User Still has Bounceback balance ' . $bounceback;
                            return response($message['err']);
                        }
                        if ($data['balance'] > DB::table('users')->where('id', $sequence)->pluck('reward')[0]) {
                            $message['err'] = 'User does not have enough redeem after paying bounce back';
                            return response($message['err']);
                        }
                    }
                    $reward = DB::table('users')->where('id', $sequence)->pluck('reward')[0];
                    $vendoramount = DB::table('users')->where('id', $vendorid)->pluck('amount')[0];
                    $vendorreward = DB::table('users')->where('id', $vendorid)->pluck('reward')[0];
                    $redeemamount = $data['balance'];
                    $previous = $reward;
                    $reward = $reward - $redeemamount;
                    $previousvb = $vendorreward;
                    $vendorreward = $vendorreward + $redeemamount;
                    $todayreport = DB::table($vendorid . '_accountreport')->whereDate('created_at', '=', date('Y-m-d', time()))->get()->toArray();
                    $todayreport = array_map(function ($value) {
                        return (array)$value;
                    }, $todayreport);

                    if (count($todayreport) > 0) {
                        $todayreportid = $todayreport[0]['id'];
                        $deposit = $todayreport[0]['deposit'];
                        $Redeems = $todayreport[0]['Redeems'] + $data['balance'];
                        $profit = $deposit - $Redeems;
                        $payout = 0;
                        if ($Redeems > 0 && $profit > 0) {
                            $payout = ($profit / $Redeems) * 100;
                        }
                        DB::table($vendorid . '_accountreport')->where('id', $todayreportid)->update([
                            'account' => $vendorname, 'deposit' => $deposit, 'Redeems' => $Redeems,
                            'profit' => round($profit, 2), 'payout' => round($payout, 2), 'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                        ]);
                    } else {
                        DB::table($vendorid . '_accountreport')->insert([
                            'account' => $vendorname, 'deposit' => round($data['balance'], 2),
                            'created_at' => date('Y-m-d', time()), 'updated_at' => now()
                        ]);
                    }
                    DB::table('users')->where('id', $sequence)->update(
                        ['reward' => $reward],
                    );
                    DB::table('users')->where('id', $vendorid)->update(
                        ['reward' => $vendorreward, 'amount' => $vendoramount + $redeemamount],
                    );
                    ///only use from deposit redeem and revert and games of user
                    DB::table($vendorid . '_accounthistory')->insert([
                        'account' => $username, 'amount' => $redeemamount, 'description' => 'redeem', 'name' => $name,
                        'frombalance' => $previous, 'bounce' => '', 'tobalance' => $reward, 'created_at' => now(), 'updated_at' => now(), 'color' => 6
                    ]);
                    $message['err'] = 'User Withdrawl Redeem';
                    return response($message['err']);
                    break;
            }
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return 'dsasd';
    }

    public function usershistory(Request $request)
    {
        try {
            $sequence = $request->input('sequence');
            $vendorid = auth()->user()->id;
            $account = DB::table('users')->where('id', $sequence)->pluck('username')[0];
            $data['name'] = DB::table('users')->where('id', $sequence)->select('name')->get();
            switch ($request->input('type')) {
                case 'logs':
                    $data['data'] = DB::table('logs')->where('user_id', $sequence)->orderBy('id', 'DESC')->get()->toArray();
                    $data['data'] = array_map(function ($value) {
                        return (array)$value;
                    }, $data['data']);
                    break;
                default:
                    $data['data'] = DB::table($vendorid . '_accounthistory')->where('account', $account)->orderBy('id', 'DESC')->get();
            }
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return response($data);
    }
    public function accounthistory(Request $request)
    {
        try {
            $vendorid = auth()->user()->id;
            $account = DB::table('users')->where('id', $request->input('sequence'))->pluck('username')[0];
            $data['data'] = DB::table($vendorid . '_accounthistory')->where('account', $account)->orderBy('id', 'DESC')->get();
            $data['c'] = DB::table('users')->where('id', $vendorid)->get();
            $data['u'] = DB::table('users')->where('username', $account)->get();
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return response($data);
    }
    public function vendorreport()
    {
        try {
            $vendorid = auth()->user()->id;
            $data['data'] = DB::table($vendorid . '_accountreport')->orderBy('id', 'DESC')->get();
            $data['c'] = DB::table('users')->where('id', $vendorid)->get();
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return response($data);
    }
    public function transctionhistory()
    {
        try {
            $vendorid = auth()->user()->id;
            $data['data'] = DB::table($vendorid . '_transctionhistory')->orderBy('id', 'DESC')->get();
            $data['c'] = DB::table('users')->where('id', $vendorid)->get();
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return response($data);
    }

    public function vendordisputes()
    {
        try {
            $vendorid = auth()->user()->id;
            $users = DB::table('vendorsuser')->where('vendorid', $vendorid)->pluck('userid');
            $data['data'] = DB::table('users')->whereIn('id', $users)->where('close', true)->get();
            $data['c'] = DB::table('users')->where('id', $vendorid)->get();
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return response($data);
    }
    public function sendsms(Request $request)
    {
        try {
            $number = $request->input('number');
            $platform = $request->input('platform');
            try {
                Log::info($number);
                $client = new \GuzzleHttp\Client();
                $response = $client->get('http://renonights.xyz/sendsms?number=' . $number . '&platform=' . $platform);
            } catch (Exception $exception) {
                Log::info($exception);
            }
        } catch (Exception $exception) {
            Log::info($exception);
        }

        return "sms sent to the given number";
    }
}
