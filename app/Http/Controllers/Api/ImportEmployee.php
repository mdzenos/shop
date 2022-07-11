<?php

namespace App\Http\Controllers\Api;

use App\Models\Domain;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ZohoController;

class ImportEmployee extends Controller
{
    //

    const Token = 'WoaCoxpX6yUoEfZnAy4ERszWRYFOyUcipzxqqlpXlDaFeU6vSPgJhbwWmtbA';
    const Limit = 200;

    public function import(Request $request)
    {
        set_time_limit(-1);
        ini_set("max_input_time", "-1");

        $token = self::Token;
        if (empty($token)) {
            return ['code' => 404, 'msg' => 'Missing request parameter'];
        }
        $config = $this->getConfig($token);
        $zoho = new ZohoController($config);

        $domain = json_decode(Domain::where('token', '=', $token)->first(), true);
        $EmployeeID = $request->EmployeeID;
        $record = json_decode(Employee::where('code', '=', $EmployeeID)->first(), true);

        $i = -1;
        while (true) {
            $i++;
            $body['sIndex'] = $i * self::Limit + 1;
            $body['limit'] = self::Limit;

            $employees = $zoho->getRecords($config['employee']['getRecords'], $body);
            foreach ($employees as $employee) {
                try {
                    if ($employee['EmployeeID'] == $EmployeeID) {
                        $data = $zoho->getRecordByID($employee['Zoho_ID'], $config['employee']['getRecordByID']);
                    }else {
                        continue;
                    }
                    
                    if (!empty($data['errors'])) {
                        continue;
                    }
                    
                    if (!$record) {
                        Employee::create([
                            'domain_id' =>  $domain['id'],
                            'role_id' => $employee['Zoho_ID'],
                            'zoho_id' => $data['Role.ID'],
                            'code' => $employee['EmployeeID'],
                            'full_name' => $data['LastName'] . ' ' . $data['FirstName'],
                            'email' => $data['EmailID'],
                            'job_level' => $data['Job_Level'],
                            'job_rank' => $data['Job_Rank'],
                            'department_id' => $data['Department.ID'],
                            'department_name' => $data['Department'],
                            'division_id' => $data['Division.ID'],
                            'division_name' => $data['Division'],
                            'date_of_exit' => $data['Dateofexit'],
                            'date_of_joining' => $data['Dateofjoining'],
                            'date_of_birth' => date("Y-m-d", strtotime($data['Date_of_birth'])),
                            'location_name' => $data['LocationName'],
                            'location_id' => $data['LocationName.ID'],
                            'reporting_to' => $data['Reporting_To.MailID'],
                            'reporting_to_id' => $data['Reporting_To.ID'],
                            'active' => $data['Employeestatus'] ? '1' : 0,
                            'no_dependent' => '0',
                            'avatar' =>  '',
                        ]);
                        return response()->json(['message' => 'Created',], 200);
                    } else if ($record) {
                        Employee::where('code', '=', $EmployeeID)->first()->update([
                            'domain_id' =>  $domain['id'],
                            'role_id' => $employee['Zoho_ID'],
                            'zoho_id' => $data['Role.ID'],
                            'full_name' => $data['LastName'] . ' ' . $data['FirstName'],
                            'email' => $data['EmailID'],
                            'job_level' => $data['Job_Level'],
                            'job_rank' => $data['Job_Rank'],
                            'department_id' => $data['Department.ID'],
                            'department_name' => $data['Department'],
                            'division_id' => $data['Division.ID'],
                            'division_name' => $data['Division'],
                            'date_of_exit' => $data['Dateofexit'],
                            'date_of_joining' => $data['Dateofjoining'],
                            'date_of_birth' => date("Y-m-d", strtotime($data['Date_of_birth'])),
                            'location_name' => $data['LocationName'],
                            'location_id' => $data['LocationName.ID'],
                            'reporting_to' => $data['Reporting_To.MailID'],
                            'reporting_to_id' => $data['Reporting_To.ID'],
                            'active' => $data['Employeestatus'] ? '1' : 0,
                            'no_dependent' => '0',
                            'avatar' =>  '',
                        ]);
                        return response()->json(['message' => 'Updated',], 200);
                    } else {
                        continue;
                    }
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    continue;
                }
            }
        }
    }
}
