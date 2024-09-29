<?php

namespace App\Http\Controllers;
use App\Models\Method;

class MethodController extends Controller
{
    public function get()
    {
        $methods = Method::all();
        return response()->json($methods);
    }
}
