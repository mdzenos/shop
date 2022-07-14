<?php

namespace App\Http\Controllers\Api;

use App\Models\Domain;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ZohoController;

class Test extends Controller
{
    public function test(Request $request)
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
            dd($data);
            if (!empty($data['errors'])) {
                return ['code' => 404, 'msg' => 'Data error'];
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            die($e);
        }
    }
}
