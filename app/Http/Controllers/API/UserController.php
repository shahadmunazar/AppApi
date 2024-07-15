<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function dashboard()
    {
        try {
            $user  = Auth::user();
            return response()->json(['status' => 200, 'data' => $user]);
        } catch (\Throwable $th) {
            dd($th);
            //throw $th;
        }
    }
}
