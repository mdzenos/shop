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

        $EmployeeID = $request->EmployeeID;
        $record = Employee::where('EmployeeID', $EmployeeID);
dd($employees);
        foreach ($employees as $employee) {

            try {
                if($EmployeeID == $employee['EmployeeID']){//Zoho_ID 412762000139836883
                    $data = $zoho->getRecordByID($employee['Zoho_ID'], $config['employee']['getRecordByID']);
                }else if (!empty($data['errors'])) {
                    continue;
                }

                if(!$record->first()){
                    Employee::create([
                        'role_id' => $data['Role.ID'],
                        'code' => $data['EmployeeID'],
                        'zoho_id' => $data['Zoho_ID'],
                        'zoho_user_id' => $data['783925774'],
                        'full_name' => $data['LastName'].' '.$data['FirstName'],
                        'email' => $data['EmailID'],
                        'job_level' => $data['Job_Level'],
                        'job_rank' => $data['Job_Rank'],
                        'department_id' => $data['Department.ID'],
                        'department_name' => $data['Department'],
                        'division_id' => $data['Division.ID'],
                        'division_name' => $data['Division'],
                        'date_of_exit' => $data['Dateofexit'],
                        'date_of_joining' => $data['Dateofjoining'],
                        'date_of_birth' => $data['Date_of_birth'],
                        'location_name' => $data['LocationName'],
                        'location_id' => $data['LocationName.ID'],
                        'reporting_to' => $data['Reporting_To.MailID'],
                        'reporting_to_id' => $data['Reporting_To.ID'],
                        'active' => $data['Employeestatus'],
                    ]);
                    return response()->json(['message' => 'Created',], 201);
                }else if($record->first()){
                    $record->update([
                        'role_id' => $data['Role.ID'],
                        'code' => $data['EmployeeID'],
                        'zoho_id' => $data['Zoho_ID'],
                        'zoho_user_id' => $data['783925774'],
                        'full_name' => $data['LastName'].' '.$data['FirstName'],
                        'email' => $data['EmailID'],
                        'job_level' => $data['Job_Level'],
                        'job_rank' => $data['Job_Rank'],
                        'department_id' => $data['Department.ID'],
                        'department_name' => $data['Department'],
                        'division_id' => $data['Division.ID'],
                        'division_name' => $data['Division'],
                        'date_of_exit' => $data['Dateofexit'],
                        'date_of_joining' => $data['Dateofjoining'],
                        'date_of_birth' => $data['Date_of_birth'],
                        'location_name' => $data['LocationName'],
                        'location_id' => $data['LocationName.ID'],
                        'reporting_to' => $data['Reporting_To.MailID'],
                        'reporting_to_id' => $data['Reporting_To.ID'],
                        'active' => $data['Employeestatus'],
                    ]);
                    return response()->json(['message' => 'Updated',], 201);
                }else{
                    return response()->json(['message' => 'Failed',], 201);
                }

            } catch (\GuzzleHttp\Exception\RequestException $e) {
                continue;
            }
        }
    }
}
