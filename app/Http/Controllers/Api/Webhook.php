<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Webhook extends Controller
{
    public function store(Request $request){
        $data = Employee::where('EmployeeID', $request->EmployeeID);
        if(!$data->first()){
            Employee::create($request->all());
            return response()->json(['message' => 'Created',], 201);
        }
            $data->update([
                'FirstName' => $request->FirstName,
                'LastName' => $request->LastName,
                'EmailID' => $request->EmailID,
            ]);
            return response()->json(['message' => 'Updated',], 201);
    }
}
