<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Webhook extends Controller
{
    public function store(Request $request){
        
        if(!Employee::where('EmployeeID', $request->EmployeeID)->first()){
            Employee::create($request->all());
            return response()->json(['message' => 'Created',], 201);
        }
            Employee::save($request->all());
            return response()->json(['message' => 'Updated',], 201);
    }
}
