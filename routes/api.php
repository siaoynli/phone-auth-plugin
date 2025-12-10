<?php

use Illuminate\Support\Facades\Route;
use Siaoynli\PhoneAuth\Controllers\PhoneAuthController;

Route::get('/check', function () {
  return response()->json(['success' => true, 'message' => '检查成功']);
});

// 无需认证的路由
Route::get('/send-code', [PhoneAuthController::class, 'sendCode']);
Route::post('/login', [PhoneAuthController::class, 'login']);

// 需要认证的路由
Route::middleware('auth:sanctum')->group(function () {
  Route::post('/logout', [PhoneAuthController::class, 'logout']);
  Route::get('/profile', [PhoneAuthController::class, 'profile']);
});
