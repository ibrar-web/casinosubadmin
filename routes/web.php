<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VendorHomeController;
use App\Http\Controllers\admin\SubadminController;
use App\Http\Controllers\SubadminVendorController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/logoutselfv', function () {
    Auth::logout();
    return view('auth.login');
});
Auth::routes();
Route::post('/login',[App\Http\Controllers\Auth\LoginController::class,'login']);
Route::middleware('auth:web')->group(function () {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/subadmin/vendorslist/init', [SubadminVendorController::class, 'vendorslist']);
Route::post('/subadmin/vendorsregister', [SubadminVendorController::class, 'vendorsregister']);
Route::post('/subadmin/vendorshistory', [SubadminVendorController::class, 'vendorshistory']);

Route::post('/subadmin/transctionhistory/init', [SubadminVendorController::class, 'transctionhistory']);
});

