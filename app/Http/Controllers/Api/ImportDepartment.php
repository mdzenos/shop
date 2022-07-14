<?php

namespace App\Http\Controllers\Api;

use App\Models\Domain;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ZohoController;

class ImportDepartment extends Controller
{
    public function import(Request $request)
    {
        try {
            $token  = env('TOKEN_ZOHO');
            if (empty($token)) {
                return ['code' => 404, 'msg' => 'Missing request parameter'];
            }
            $config = $this->getConfig($token);
            $zoho = new ZohoController($config);
            $Zoho_ID = $request->Zoho_ID;
            $data = $zoho->getRecordByID($Zoho_ID, $config['department']['getRecordByID']);
            if (!empty($data['errors'])) {
                return ['code' => 404, 'msg' => 'Data error'];
            }
            $record = json_decode(Department::where('zoho_id', '=', $Zoho_ID)->first(), true);
            $domain = json_decode(Domain::where('token', '=', $token)->first(), true);

            if (!$record) {
                Department::create([
                    'employee_id' => $data['Department_Lead.ID'] ? $data['Department_Lead.ID'] : '0',
                    'zoho_id' => $Zoho_ID,
                    'department_code' => '',
                    'department_name' => $data['Department'],
                    'email' => $data['MailAlias'],
                    'department_lead_id' => $data['Department_Lead.ID'] ? $data['Department_Lead.ID'] : '0',
                    'department_lead_name' => $data['Department_Lead'],
                    'department_lead_code' => $data['Department_Lead.MailID'],
                    'added_by' => $data['AddedBy'],
                    'department_parent_name' => $data['Parent_Department'],
                    'department_parent_id' => $data['Parent_Department.ID'] ? $data['Parent_Department.ID'] : 0,
                    'domain_id' =>  $domain['id'],
                    'added_time' =>  date("Y-m-d H:i:s", strtotime($data['AddedTime'])),
                    'created_at' => date("Y-m-d H:i:s", strtotime('now')),
                    'updated_at' =>  date("Y-m-d H:i:s", strtotime($data['ModifiedTime'])),
                ]);
                return response()->json(['message' => 'Created',], 200);
            }
            Department::where('zoho_id', '=', $Zoho_ID)->first()->update([
                'employee_id' => $data['Department_Lead.ID'] ? $data['Department_Lead.ID'] : '0',
                'department_code' => '',
                'department_name' => $data['Department'],
                'email' => $data['MailAlias'],
                'department_lead_id' => $data['Department_Lead.ID'] ? $data['Department_Lead.ID'] : '0',
                'department_lead_name' => $data['Department_Lead'],
                'department_lead_code' => $data['Department_Lead.MailID'],
                'added_by' => $data['AddedBy'],
                'department_parent_name' => $data['Parent_Department'],
                'department_parent_id' => $data['Parent_Department.ID'] ? $data['Parent_Department.ID'] : 0,
                'domain_id' =>  $domain['id'],
                'added_time' =>  date("Y-m-d H:i:s", strtotime($data['AddedTime'])),
                'created_at' => date("Y-m-d H:i:s", strtotime('now')),
                'updated_at' =>  date("Y-m-d H:i:s", strtotime($data['ModifiedTime'])),
            ]);
            return response()->json(['message' => 'Updated',], 200);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return ['code' => 404, 'msg' => 'Error: '.$e];
        }
    }
}
