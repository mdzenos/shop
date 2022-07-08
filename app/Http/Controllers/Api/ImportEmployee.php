<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ZohoController;

class ImportEmployee extends Controller
{
    
    const Token= 'WCS7SZFHHW2YS19D5gFFBjRoJ0yE7NT8thVcmomLeClg8suFiKOnq8W0yxrD';

    public function import(Request $request){
        set_time_limit(-1);
        ini_set("max_input_time", "-1");

        $token = self::Token;
        if (empty($token)) {
            return ['code' => 404, 'msg' => 'Missing request parameter'];
        }
        $config = $this->getConfig($token);
        $zoho = new ZohoController($config);
        $employees = $zoho->getRecords($config['employee']['getRecords']);
        dd($employees);

        $data= $request->all();
        dd($employees);


        // dua vao token de lay config form
        /*"getDataByID" => "forms/employee/getDataByID"
        "getRecordByID" => "forms/employee/getRecordByID"
        "getRecords" => "forms/employee/getRecords"
        "getRecordCount" => "forms/employee/getRecordCount"
        "insertRecord" => "forms/json/employee/insertRecord"
        "updateRecord" => "forms/json/employee/updateRecord" */

    }
    public function store(Request $request){
        $data = Employee::where('EmployeeID', $request->EmployeeID);
        $auth = $request->token === 'WoaCoxpX6yUoEfZnAy4ERszWRYFOyUcipzxqqlpXlDaFeU6vSPgJhbwWmtbA';
        if($auth && !$data->first()){
            Employee::create($request->all());
            return response()->json(['message' => 'Created',], 201);
        }else if($auth && $data->first()){
            $data->update([
                'FirstName' => $request->FirstName,
                'LastName' => $request->LastName,
                'EmailID' => $request->EmailID,
            ]);
            return response()->json(['message' => 'Updated',], 201);
        }else{
            return response()->json(['message' => 'Failed',], 201);
        }
    }
}
