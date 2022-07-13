<?php

namespace App\Http\Controllers\Api;

    use App\Models\Domain;
    use App\Models\Project;
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

            $Project_Code = $request->Project_Code;
            $record = json_decode(Project::where('project_code', '=', $Project_Code)->first(), true);

            $i = -1;
            while (true) {
                $i++;
                $body['sIndex'] = $i * self::Limit + 1;
                $body['limit'] = self::Limit;
                $Projects = $zoho->getRecords($config['smart_project']['getRecords'], $body);

                foreach ($Projects as $Project) {
                    try {
                        if ((string)$Project['Project_Code'] == $Project_Code) {
                            $data = $zoho->getRecordByID($Project['Zoho_ID'], $config['smart_project']['getRecordByID']);                            
                            if (!empty($data['errors'])) {continue;}
                        }else{ continue; }

                        if (!$record) {
                            
                            Project::create([
                                'zoho_id' => $Project['Zoho_ID'],
                                'project_code' => $data['Project_Code'],
                                'project_name' => $data['Project_Name'],
                                'project_manager_emp_code' => $data['ProjectManager'],
                                'project_manager_emp_zoho_id' => $data['ProjectManager.ID'],
                                'div_project_zoho_id' => $data['Division.ID'],
                                'div_project_name' => $data['Division'],
                                'div_manager_emp_id' => $data['ProjectManager.ID'],
                                'status' => $data['Status'],
                                'project_user' => $data['ProjectUsers.ID'],
                                'start_date' => date("Y-m-d", strtotime($data['Start_Date'])),
                                'end_date' => date("Y-m-d", strtotime($data['End_Date'])),
                                'project_type' => $data['Project_Type'],
                                'project_size' => $data['Project_Size'],
                                'created_by' => explode("-",$data['ProjectOwner'])[1],
                                'created_at' => date("Y-m-d", strtotime($data['AddedTime'])),
                                'updated_at' => date("Y-m-d", strtotime($data['ModifiedTime'])),
                                'updated_by' => explode("-",$data['ModifiedBy'])[0],
                                'domain_id' =>  $domain['id'],
                            ]);
                            return response()->json(['message' => 'Created',], 200);
                        } else if ($record) {
                            Project::where('project_code', '=', $data['Project_Code'])->first()->update([
                                'project_name' => $data['Project_Name'],
                                'project_manager_emp_code' => $data['ProjectManager'],
                                'project_manager_emp_zoho_id' => $data['ProjectManager.ID'],
                                'div_project_zoho_id' => $data['Division.ID'],
                                'div_project_name' => $data['Division'],
                                'div_manager_emp_id' => $data['ProjectManager.ID'],
                                'status' => $data['Status'],
                                'project_user' => $data['ProjectUsers.ID'],
                                'start_date' => date("Y-m-d", strtotime($data['Start_Date'])),
                                'end_date' => date("Y-m-d", strtotime($data['End_Date'])),
                                'project_type' => $data['Project_Type'],
                                'project_size' => $data['Project_Size'],
                                'created_by' => explode("-",$data['ProjectOwner'])[1],
                                'created_at' => date("Y-m-d", strtotime($data['AddedTime'])),
                                'updated_at' => date("Y-m-d", strtotime($data['ModifiedTime'])),
                                'updated_by' => explode("-",$data['ModifiedBy'])[0],
                                'domain_id' =>  $domain['id'],
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
