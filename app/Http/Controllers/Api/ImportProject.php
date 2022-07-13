<?php

namespace App\Http\Controllers\Api;

use App\Models\Domain;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ZohoController;

class ImportProject extends Controller
{
    const Limit = 200;

    public function import(Request $request)
    {
        set_time_limit(-1);
        ini_set("max_input_time", "-1");

        $token  = env('TOKEN_ZOHO');
        if (empty($token)) {
            return ['code' => 404, 'msg' => 'Missing request parameter'];
        }
        $config = $this->getConfig($token);
        $zoho = new ZohoController($config);
        $domain = json_decode(Domain::where('token', '=', $token)->first(), true);

        $DepartmentName = $request->DepartmentName;
        $record = json_decode(Department::where('department_name', '=', $DepartmentName)->first(), true);

        $i = -1;
        while (true) {
            $i++;
            $body['sIndex'] = $i * self::Limit + 1;
            $body['limit'] = self::Limit;
            $Departments = $zoho->getRecords($config['department']['getRecords'], $body);
            foreach ($Departments as $Depart) {
                try {
                    if ($Depart['Department'] == $DepartmentName) {
                        $data = $zoho->getRecordByID($Depart['Zoho_ID'], $config['department']['getRecordByID']);
                    }else{continue;}

                    if (!empty($data['errors'])){continue;}

                    if (!$record) {
                        Department::create([
                            'employee_id' => $data['Department_Lead.ID']??0,
                            'zoho_id' => $Depart['Zoho_ID'],
                            'department_code' => '',
                            'department_name' => $data['Department'],
                            'email' => $data['MailAlias'],
                            'department_lead_id' => $data['Department_Lead.ID']?$data['Department_Lead.ID']:'0',
                            'department_lead_name' => $data['Department_Lead'],
                            'department_lead_code' => $data['Department_Lead.MailID'],
                            'added_by' => $data['AddedBy'],
                            'department_parent_name' => $data['Parent_Department'],
                            'department_parent_id' => $data['Parent_Department.ID']?$data['Parent_Department.ID']:0,
                            'domain_id' =>  $domain['id'],
                        ]);
                        return response()->json(['message' => 'Created',], 200);
                        sleep(1);
                    } else if ($record) {
                        Department::where('department_name', '=', $data['Department'])->first()->update([
                            'employee_id' => $data['Department_Lead.ID']??0,
                            'zoho_id' => $Depart['Zoho_ID'],
                            'department_code' => '',
                            'email' => $data['MailAlias'],
                            'department_lead_id' => $data['Department_Lead.ID']?$data['Department_Lead.ID']:'0',
                            'department_lead_name' => $data['Department_Lead'],
                            'department_lead_code' => $data['Department_Lead.MailID'],
                            'added_by' => $data['AddedBy'],
                            'department_parent_name' => $data['Parent_Department'],
                            'department_parent_id' => $data['Parent_Department.ID']?$data['Parent_Department.ID']:0,
                            'domain_id' =>  $domain['id'],
                        ]);
                        return response()->json(['message' => 'Updated',], 200);
                        sleep(1);
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
