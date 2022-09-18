<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\LoanController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [UserAuthController::class,'register']);
Route::post('/login', [UserAuthController::class,'login']);

Route::post('/loan_request', [LoanController::class,'loanRequest'])->middleware('auth:api');

Route::post('/loan_approve', [LoanController::class,'loanApprove'])->middleware('auth:api');

Route::post('/loan_details', [LoanController::class,'loanDetails'])->middleware('auth:api');

Route::post('/loan_repayment', [LoanController::class,'loanRepayment'])->middleware('auth:api');
