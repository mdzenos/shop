<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Webhook extends Controller
{
    public function store(Request $request){
        Employee::create($request->all());
        return response()->json(['message' => 'Thành công',], 201);
    }
}
