<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use GuzzleHttp\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {

    }

    public function reponseMessenger()
    {
        return [
            'code' => 0,//Khong co loi
            'msg' => '',
            'type' => '',
            'action' => '',
            'redrect' => 0,
            'reload' => 0,//Reload lai trang hay ko
            'data' => []
        ];
    }

    public function logDebug($log = true, $module = '', $msg = '', $data = [])
    {
        if($log) {

            if($module != ''){
                $fileName = $module . date('Y-m-d') . '.log';
            }else{
                $fileName = 'monitor-' . date('Y-m-d') . '.log';
            }

            $path_dir = storage_path('logs/' . $fileName);

            if (!empty($data)) {
                $txt = date('Y-m-d H:i:s') . ' : ' . $msg .' : '. print_r($data, true) . PHP_EOL;
            } else {
                $txt = date('Y-m-d H:i:s') . ' : ' . $msg . PHP_EOL;
            }

            if (file_exists($path_dir)) {
                file_put_contents($path_dir, $txt, FILE_APPEND);
            }else{
                //Create the file
                file_put_contents($path_dir, $txt, FILE_APPEND);
                //Make it writeable
                chmod($path_dir,0777);
            }
        }
    }

    public function logError($log = true, $module = '', $msg = '', $data = [])
    {
        if($module != ''){
            $fileName = $module . date('Y-m-d') . '.log';
        }else{
            $fileName = 'monitor-' . date('Y-m-d') . '.log';
        }

        $path_dir = storage_path('logs/' . $fileName);

        if (!empty($data)) {
            $txt = date('Y-m-d H:i:s') . ' : ' . $msg .' : '. print_r($data, true) . PHP_EOL;
        } else {
            $txt = date('Y-m-d H:i:s') . ' : ' . $msg . PHP_EOL;
        }

        if (file_exists($path_dir)) {
            file_put_contents($path_dir, $txt, FILE_APPEND);
        }else{
            //Create the file
            file_put_contents($path_dir, $txt, FILE_APPEND);
            //Make it writeable
            chmod($path_dir,0777);
        }
    }

    /**
     * @function wirte log file
     */
    public function logFile($log = true, $module = '', $msg = '', $data = [])
    {
        if($module != ''){
            $fileName = $module . date('Y-m-d') . '.log';
        }else{
            $fileName = 'monitor-' . date('Y-m-d') . '.log';
        }

        $path_dir = storage_path('logs/' . $fileName);

        if (!empty($data)) {
            $txt = date('Y-m-d H:i:s') . ' : ' . $msg .' : '. print_r($data, true) . PHP_EOL;
        } else {
            $txt = date('Y-m-d H:i:s') . ' : ' . $msg . PHP_EOL;
        }

        if (file_exists($path_dir)) {
            file_put_contents($path_dir, $txt, FILE_APPEND);
        }else{
            //Create the file
            file_put_contents($path_dir, $txt, FILE_APPEND);
            //Make it writeable
            chmod($path_dir,0777);
        }
    }

    public function sendRequestToPayroll($action, $arrParram = [], $method = 'POST', $type = '')
    {
        $token  = isset($arrParram['token'])?$arrParram['token']:'';
        if($token == ''){
            return json_encode(['code' => 1, 'msg' => 'ERROR : Unauthorized access']);
        }

        $url    = env('SBS_PAYROLL_API', 'http://sbs-payroll.de3.smartosc.com/')  . $action;

        if($type == 'punch'){
            $url    = env('SBS_PAYROLL_API_PUNCH', 'http://sbs-payroll.de3.smartosc.com/')  . $action;
        }else{
            if($token == 'WoaCoxpX6yUoEfZnAy4ERszWRYFOyUcipzxqqlpXlDaFeU6vSPgJhbwWmtbA'){
                $url    = 'http://sbs-payroll.hn.smartosc.com/' . $action;

                /* call with method GET to get response data */
                if($action == "sbs/employee/get-by-employee")
                    $method = "GET";
            }
        }

        $arrParam = [];
        if(strtolower($method) == 'get'){
            $typeParam = 'query';
        }else{
            $typeParam = 'form_params';
        }
        $arrParam[$typeParam]               = $arrParram;
        $arrParam[$typeParam]['token']      = $token;
        //$arrParam[$typeParam]['headers']    = ['Content-Type' => 'application/json', 'Authorization' => 'Zoho-authtoken ' . $token];

        $client = new  Client();
        $resault = $client->request(strtoupper($method), $url, $arrParam);

        if ($resault->getStatusCode() == 200) {
            $respons = $resault->getBody()->getContents();
            return json_decode($respons, true);
        }else{
            return json_encode(['code' => -1, 'msg' => 'ERROR']);
        }
    }

    public function sendRequestToHanet($action, $arrParram = [], $method = 'POST')
    {
        $url    = 'https://partner.hanet.ai/' . $action;
        $arrParam = [];
        if(strtolower($method) == 'get'){
            $typeParam = 'query';
        }else{
            $typeParam = 'form_params';
        }
        $arrParam[$typeParam]               = $arrParram;
        $arrParam[$typeParam]['token']      = env('HANET_TOKEN');
//        $arrParam['headers'] = [
//            //'Authorization' => 'Bearer ' . env('HANET_TOKEN'),
//            'Content-Type' => 'application/json',
//        ];

        $client = new  Client();
        $resault = $client->request(strtoupper($method), $url, $arrParam);

        if ($resault->getStatusCode() == 200) {
            $respons = $resault->getBody()->getContents();
            return json_decode($respons, true);
        }else{
            return json_encode(['code' => -1, 'msg' => 'ERROR']);
        }
    }

    public function sendRequestToPunch($action, $arrParram = [], $method = 'POST')
    {
        $url    = env('SBS_PUNCH_API', 'http://sbs-others.hn.smartosc.com/')  . $action;
        $token  = isset($arrParram['token'])?$arrParram['token']:'';
        if($token == ''){
            return json_encode(['code' => 1, 'msg' => 'ERROR : Unauthorized access']);
        }

        $arrParam = [];
        if(strtolower($method) == 'get'){
            $typeParam = 'query';
        }else{
            $typeParam = 'form_params';
        }
        $arrParam[$typeParam]               = $arrParram;
        $arrParam[$typeParam]['token']      = $token;
        $arrParam[$typeParam]['headers']    = ['Content-Type' => 'application/json', 'Authorization' => 'Zoho-authtoken ' . $token];

        $client = new  Client();
        $resault = $client->request(strtoupper($method), $url, $arrParam);

        if ($resault->getStatusCode() == 200) {
            $respons = $resault->getBody()->getContents();
            return json_decode($respons, true);
        }else{
            return json_encode(['code' => -1, 'msg' => 'ERROR']);
        }
    }


    public function sendRequestToPunchHcm($action, $arrParram = [], $method = 'POST')
    {
        $url    = env('SBS_PUNCH_HCM_API', 'http://punch.hcm.smartosc.com/')  . $action;
        $token  = isset($arrParram['token'])?$arrParram['token']:'';

        $arrParam = [];
        if(strtolower($method) == 'get'){
            $typeParam = 'query';
        }else{
            $typeParam = 'form_params';
        }
        $arrParam[$typeParam]               = $arrParram;

        $client = new  Client();
        $resault = $client->request(strtoupper($method), $url, $arrParam);

        if ($resault->getStatusCode() == 200) {
            $respons = $resault->getBody()->getContents();
            return json_decode($respons, true);
        }else{
            return json_encode(['code' => -1, 'msg' => 'ERROR']);
        }
    }

    public function getConfig($token = '')
    {
        $redis = Redis::connection();
        $arrConfig = $redis->get(env('REDIS_CONFIG_FORM', 'config_form'));

        if(empty($arrConfig)){

            $response = $this->getListConfigForm();
            $redis->set(env('REDIS_KEY_CONFIG_FORM', 'config_form'), json_encode($response));
        }else{
            $response = json_decode($arrConfig,true);
        }

        if(isset($response[$token]) && !empty($response[$token])){

            return $response[$token];

        }else{
            return [];
        }
    }

    public function getListConfigForm()
    {
        $response = [];
        //Nếu là backend đã login
        $checkExist = Domain::where('status', '=', 1)->get();
        if(!empty($checkExist)){
            foreach ($checkExist as $data) {

                $response[$data->token] = $data->toArray();
                if (!empty($data->form)) {

                    if ($data->key != 'recruit') {
                        $response[$data->token]['attendance']['getUserReport'] = 'attendance/getUserReport';
                        $response[$data->token]['attendance']['getAttendanceEntries'] = 'attendance/getAttendanceEntries';
                        $response[$data->token]['attendance']['getShiftConfiguration'] = 'attendance/getShiftConfiguration';
                        $response[$data->token]['attendance']['bulkImport'] = 'attendance/bulkImport';
                        $response[$data->token]['attendance']['getRegularizationRecords'] = 'attendance/getRegularizationRecords';
                        $response[$data->token]['deleteRecords'] = 'deleteRecords';

                    }

                    foreach ($data->form as $item) {

                        if ($data->key == 'recruit') {
                            $response[$data->token][$item->form_slug]['getDataByID'] = $item->form_name . '/getDataByID';
                            $response[$data->token][$item->form_slug]['getRecordById'] = $item->form_name . '/getRecordById';
                            $response[$data->token][$item->form_slug]['getRecordCount'] = $item->form_name . '/getRecordCount';
                            $response[$data->token][$item->form_slug]['getRecords'] = $item->form_name . '/getRecords';
                            $response[$data->token][$item->form_slug]['addRecords'] = $item->form_name . '/addRecords';
                            $response[$data->token][$item->form_slug]['updateRecords'] = $item->form_name . '/updateRecords';
                            $response[$data->token][$item->form_slug]['uploadDocument'] = $item->form_name . '/uploadDocument';
                            $response[$data->token][$item->form_slug]['getSearchRecords'] = $item->form_name . '/getSearchRecords';

                        } else {
                            $response[$data->token][$item->form_slug]['components'] = 'forms/' . $item->form_name . '/components';
                            $response[$data->token][$item->form_slug]['getDataByID'] = 'forms/' . $item->form_name . '/getDataByID';
                            $response[$data->token][$item->form_slug]['getRecordByID'] = 'forms/' . $item->form_name . '/getRecordByID';
                            $response[$data->token][$item->form_slug]['getRecords'] = 'forms/' . $item->form_name . '/getRecords';
                            $response[$data->token][$item->form_slug]['getRecordCount'] = 'forms/' . $item->form_name . '/getRecordCount';
                            $response[$data->token][$item->form_slug]['insertRecord'] = 'forms/json/' . $item->form_name . '/insertRecord';
                            $response[$data->token][$item->form_slug]['updateRecord'] = 'forms/json/' . $item->form_name . '/updateRecord';
                            $response[$data->token][$item->form_slug]['deleteRecords'] = $item->form_name;
                        }

                        if (!empty($item->labelKey)) {
                            foreach ($item->labelKey as $kl) {
                                $response[$data->token][$item->form_slug][$kl->slug . '_label'] = $kl->label;
                                $response[$data->token][$item->form_slug][$kl->slug . '_key'] = $kl->key;
                            }
                        }
                    }
                }
            }
        }

        return $response;
    }

    public function nightsDate($fromDate = '', $toDate = '')
    {

        $fromDate = date('Y-m-d', strtotime($fromDate));
        $toDate = date('Y-m-d', strtotime($toDate));
        $diff = date_diff(date_create($toDate),date_create($fromDate));

        $countDate = $diff->format("%a");
        if($countDate === ''){
            $countDate = 0;
        }
        return $countDate;
    }

    public function convertNumberToString($value = 0, $decimal = 2)
    {
        if($value == ''){
            $value = 0;
        }

        $number = str_replace(',', '', $value);

        $decimal = strlen($number);

        $num = '';
        for ($i =0; $i<=$decimal; $i++){
            $txt = substr($number, $i, 1);
            if(is_numeric(substr($number, $i, 1))){
                $num .=substr($number, $i, 1);
            }else{
                if($txt == '.'){
                    $num .= $txt;
                }elseif($txt == '-'){
                    $num .= $txt;
                }
            }
        }
        $ext = explode('.', $num);
        if(count($ext) >= 2){
            $lastNum = isset($ext[1])?$ext[1]:0;
            if(strlen($lastNum) < 2){
                $decimal = strlen($lastNum);
            }else{
                $decimal = 2;
            }
        }else{
            $decimal = 0;
        }
        if($num == ''){
            $num = 0;
        }
        return number_format($num, $decimal, ".", ",");
    }

    public function replaceNumber($value = 0, $point = ',', $replace = '')
    {
        if($value === ''){
            $value = 0;
        }

        $decimal = strlen($value);
        $num = '';
        for ($i =0; $i<=$decimal; $i++){
            $txt = substr($value, $i, 1);
            if(is_numeric(substr($value, $i, 1))){
                $num .=substr($value, $i, 1);
            }else{
                if($txt == '.'){
                    $num .= $txt;
                }elseif($txt == '-'){
                    $num .= $txt;
                }
            }
        }
        $res = str_replace($point, $replace, $num);
        if($res == ''){
            $res = 0;
        }
        return $res;
    }

    public function convertEmp($data = [])
    {
        $empTmp = '';
        if(isset($data['Emp_info'])){
            $empTmp = $data['Emp_info'];
        }elseif(isset($data['empID'])){
            $empTmp = $data['empID'];
        }elseif(isset($data['Employee'])){
            $empTmp = $data['Employee'];
        }elseif(isset($data['employee'])){
            $empTmp = $data['employee'];
        }elseif(isset($data['employee_id'])){
            $empTmp = $data['employee_id'];
        }

        $employeeId = '';
        if($empTmp){
            $extEmp     = explode('-', $empTmp);
            $employeeId = trim(isset($extEmp[1])?$extEmp[1]:$extEmp[0]);
        }
        return $employeeId;
    }

    public function convertProject($str = '')
    {
        $arrProject['code'] = '';
        $arrProject['name'] = '';
        if($str){
            $extEmp     = explode('-', $str);

            if(count($extEmp) <= 2){
                $arrProject['name'] = trim(isset($extEmp[1])?$extEmp[1]:$extEmp[0]);
                $arrProject['code'] = trim(isset($extEmp[0])?$extEmp[0]:$extEmp[0]);
            }elseif(count($extEmp) >= 3){
                $arrProject['name'] = trim(isset($extEmp[1])?$extEmp[1]:$extEmp[0]) . ' ' .trim(isset($extEmp[2])?$extEmp[2]:$extEmp[0]);
                $arrProject['code'] = trim(isset($extEmp[0])?$extEmp[0]:$extEmp[0]);
            }else{
                $projectName = isset($extEmp[2])?$extEmp[2]:'';
                $arrProject['name'] = $projectName;
                if($projectName != ''){
                    $projectName = '-'. $projectName;
                }

                $projectCode = str_replace($projectName, '', $str);
                $arrProject['code'] = $projectCode;
            }

        }
        return $arrProject;
    }

    public function convertDate($data = [])
    {
        $date = '';
        if(isset($data['Date'])){
            $date = $data['Date'];
        }elseif(isset($data['Date1'])){
            $date = $data['Date1'];
        }elseif(isset($data['date'])){
            $date = $data['date'];
        }
        return $date;
    }

    public function rangeDateResource($startDate = '', $endDate = 0)
    {
        $date1 = strtotime($startDate);
        $date2 = strtotime($endDate);

        $year1 = date('Y', $date1);
        $year2 = date('Y', $date2);

        $month1 = date('m', $date1);
        $month2 = date('m', $date2);

        $countMonth = (($year2 - $year1) * 12) + ($month2 - $month1);

        $arrMonth = [];
        if($countMonth == 0){
            $arrMonth[] = date('M-Y',strtotime($startDate));
        }elseif ($countMonth > 0){
            for ($i = 0; $i <= $countMonth; $i++){
                $currenDate = date('M-Y', strtotime($startDate. "+ " .$i. "month"));
                $arrMonth[] = $currenDate;
            }
        }
        return $arrMonth;
    }

    public function convertEffortNumber($number)
    {
        if(isset($number) && $number !== '')
        {
            if($number == 'err') {
                return $number;
            }
            $pattern = '/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>\\?\\\a-zA-Z]/';
            preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE);
            if(empty($matches)) {
                $number = round(floatval($number),1);
                if($number < 0 || $number >8)
                {
                    $error = 'err';
                    return $error;
                } else {
                    return $number;
                }
            }else {
                $error = 'err';
                return $error;
            }
        } else {
            return '';
        }
    }

    public function sumTotalEffort($dataTabular =[], $allDayAllocation = [])
    {
        $totalEffort = 0;
        if(isset($dataTabular) && !empty($dataTabular)) {
            foreach($dataTabular as $key => $item) {
                foreach($item as $key1 => $value) {
                    if($item['employee_1'] !== '' && isset($allDayAllocation[$key1]) && $allDayAllocation[$key1] !== '') {
                        $effort = $this->convertEffortNumber($value);
                        if (($key1 !== "employee_1.ID" && $key1 !== "employee_1" && $key1 !== "tabular.ROWID") && ($effort !== '' && $effort !== 'err' && 0 <= $effort && $effort <= 8)) {
                            $totalEffort += $effort;
                        }
                    }
                }
            }
        }
        return filter_var(number_format(floatval($totalEffort),2), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public function percentHour($hour = 0)
    {
        if(isset($hour) && $hour !== 0) {
            $percent = ($hour * 100)/8;
            $percentHour = '('.$hour.'h'.' | '.$percent.'%)';
            return $percentHour;
        }else {
            return 0;
        }
    }

    public function mappingParamZoho($data = [], $explode = false)
    {
        $response['employee_id'] = '';
        $response['projected'] = '';

        //Convert employee
        $arrTmp['employee_id'] = '';
        if(isset($data['Emp_info'])){
            $arrTmp['employee_id'] = $data['Emp_info'];
        }elseif(isset($data['empID'])){
            $arrTmp['employee_id'] = $data['empID'];
        }elseif(isset($data['Employee'])){
            $arrTmp['employee_id'] = $data['Employee'];
        }elseif(isset($data['employee_id'])){
            $arrTmp['employee_id'] = $data['employee_id'];
        }

        $extEmp = explode('-', $arrTmp['employee_id']);
        $response['employee_id'] = trim(isset($extEmp[1]) ? $extEmp[1] : $extEmp[0]);

        //Convert projected
        $arrTmp['projected'] = '';
        if(isset($data['Project'])){
            $arrTmp['projected'] = $data['Project'];
        }elseif(isset($data['Projected'])){
            $arrTmp['projected'] = $data['Projected'];
        }elseif(isset($data['project'])){
            $arrTmp['projected'] = $data['project'];
        }elseif(isset($data['projected'])){
            $arrTmp['projected'] = $data['projected'];
        }

        $extProject = explode('-', $arrTmp['projected']);
        $response['projected']      = trim(isset($extProject[1]) ? $extProject[1] : $extProject[0]);
        $response['projected_code'] = trim(isset($extProject[0]) ? $extProject[0] : $arrTmp['projected']);

        //Convert date
        $response['date'] = '';
        if(isset($data['Date'])){
            $response['date'] = $data['Date'];
        }elseif(isset($data['Date1'])){
            $response['date'] = $data['Date1'];
        }elseif(isset($data['date'])){
            $response['date'] = $data['date'];
        }

        //Zoho id
        $response['zoho_id'] = '';
        if(isset($data['onsite_request_id'])){
            $response['zoho_id'] = $data['onsite_request_id'];
        }elseif(isset($data['onsite_expense_id'])){
            $response['zoho_id'] = $data['onsite_expense_id'];
        }elseif(isset($data['onsite_ticket_id'])){
            $response['zoho_id'] = $data['onsite_ticket_id'];
        }elseif(isset($data['zoho_id'])){
            $response['zoho_id'] = $data['zoho_id'];
        }

        return $response;
    }

    public function getAllDomainPunch($domainId = '', $statusPunch = 1)
    {

        $response = [];
        if($domainId){
            $arrDomain = Domain::where('id', '=', $domainId)->orderBy('id','DESC')->get();
        }else{
            $arrDomain = Domain::orderBy('id','DESC')->get();
        }

        if(!empty($arrDomain)){
            foreach ($arrDomain as $item){

                if($statusPunch == 1){
                    if($item->status_punch == 0){
                        continue;
                    }
                }
                $response[$item->id]            = $this->getConfig($item->token);
                $response[$item->id]['key']     = $item->key;
                $response[$item->id]['token']   = $item->token;
            }
        }
        return $response;
    }

    public function getAllDomainSyncLocal($domainId = '', $syncLocal = 1)
    {

        $response = [];
        if($domainId){
            $arrDomain = Domain::where('id', '=', $domainId)->orderBy('id','DESC')->get();
        }else{
            $arrDomain = Domain::orderBy('id','DESC')->get();
        }

        if(!empty($arrDomain)){
            foreach ($arrDomain as $item){

                if($syncLocal == 1){
                    if($item->sync_local == 0){
                        continue;
                    }
                }
                $response[$item->id]            = $this->getConfig($item->token);
                $response[$item->id]['key']     = $item->key;
                $response[$item->id]['token']   = $item->token;
            }
        }
        return $response;
    }

    public function diffTimeHour($fromHour = '', $toHour = '', $num = false)
    {
        $diffBreak = date_diff(date_create($fromHour),date_create($toHour));
        $h = $diffBreak->format("%h");
        $m = $diffBreak->format("%i");
        $hourBreak = $h.':'.$m;
        if($num){
            $hourBreak = $h + $m / 60;
        }
        return $hourBreak;
    }

    public function convertSpecialCharacter($str) {
        //tieng viet co dau sang khong dau
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
        $str = preg_replace("/(đ)/", "d", $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
        $str = preg_replace("/(Đ)/", "D", $str);

        //xoa ki tu dac biet
        $str = preg_replace('/[^A-Za-z0-9\-]/', ' ', $str);

        //replace multiple spaces with a single space
        $str = preg_replace('!\s+!', ' ', $str);

        $str = trim($str);
        $str = str_replace('\' ', '\'', ucwords(str_replace('\'', '\' ', strtolower($str))));
        return $str;
    }

    public function rangeDate($num = 0)
    {
        $fromSalary = env('FROM_SALARY');
        $curentMonth = date('m');
        $currentYear = date('Y-');
        $currentDay = date('d');

        if($currentDay > 25 && $currentDay <= 31){
            $fromDate = $currentYear . ($curentMonth) . $fromSalary;
        }else{
            $fromDate = $currentYear . ($curentMonth - 1) . $fromSalary;
        }
        $toDate = date('Y-m-d', strtotime(date('Y-m-d 23:59:59'). "-" .$num." days"));

        $diff = date_diff(date_create($fromDate),date_create($toDate));
        $countDate = $diff->format("%a");

        $arrDate = [];
        if($countDate == 0){
            $arrDate[] = $fromDate;
        }elseif ($countDate > 0){
            // đoạn này nếu chạy từ $i = 1 thì sẽ bắt đầu từ ngày 22
            for ($i = 0; $i <= $countDate; $i++){
                $currenDate = date('Y-m-d', strtotime($fromDate. "+ " .$i. "days"));
                $arrDate[] = $currenDate;
            }
        }
        return $arrDate;
    }

    public function diffHours($firstDay = '', $lastDay = ''){
        $diff = abs(strtotime($lastDay) - strtotime($firstDay));

        $years      = floor($diff / (365*60*60*24));
        $months     = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
        $days       = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24) / (60*60*24));
        $hours      = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24) / (60*60));
        $minutes    = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60) / 60);
        $seconds    = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60));
        $hours += $minutes/60;
        return[
            'years' => $years,
            'months' => $months,
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }

    public function convertZohoNumber($number)
    {
        if(isset($number)) {
            $number = str_replace(',','',$number);
            return number_format(floatval($number), 2);
        }else{
            return number_format(0,2);
        }
    }

    public function convertToNumber($number)
    {
        if(isset($number)) {
            $number = str_replace(',','',$number);
            return floatval($number);
        }else{
            return 0.00;
        }
    }

    public function splitEmployeeCodeAndName($data = '')
    {
        $arrEmp = explode('-', $data);
        $arrEmp = array_map('trim', $arrEmp);

        $response['employee_code'] = $arrEmp[0];
        unset($arrEmp[0]);
        $empName = implode(' ', $arrEmp);

        $response['employee_name'] = $empName;

        return $response;
    }

    public function convertRecordId($recordId = '')
    {
        $extNumberId = explode('-', $recordId);
        $response = trim(isset($extNumberId[1]) ? $extNumberId[1] : $extNumberId[0]);

        return $response;
    }


    public function getConfigKeyCache($token = '', $queue = '')
    {

        if($queue != ''){
            return 'config_' . $queue . '_'  . $token;
        }else{
            return 'config_' . $token;
        }
    }

    public function getByEmployeeKeyCache($token = '', $empId = 0, $queue = '')
    {

        if($queue != ''){
            return 'employee_' . $queue . '_' . $empId . '_' . $token;
        }else{
            return 'employee_' . $empId . '_' . $token;
        }
    }

    public function getListEmployeeKeyCache($token = '', $page = 0, $queue = '')
    {

        if($queue != ''){
            return 'list_employee_' . $queue . '_' . $page . '_' . $token;
        }else{
            return 'list_employee_' . $page . '_' . $token;
        }
    }

    public function getLeaveHolidayKeyCache($token = '', $empId = 0, $queue = '')
    {

        if($queue){
            return 'leave_holiday_' . $queue . '_' . $empId . '_' . $token;
        }else{
            return 'leave_holiday_' . $empId . '_' . $token;
        }
    }

    public function getLeaveByDateKeyCache($token = '', $empId = 0, $date = '', $queue = '')
    {

        if($queue != ''){
            return 'leave_'. $queue . '_'  . $date . '_' . $empId . '_' . $token;
        }else{
            return 'leave_' . $date . '_' . $empId . '_' . $token;
        }
    }

    public function countDate($fromDay = '', $toDay = '')
    {
        $fromDate = date('Y-m-d', strtotime($fromDay));
        $toDate = date('Y-m-d', strtotime($toDay));
        $diff = date_diff(date_create($fromDate),date_create($toDate));

        $countDate = $diff->format("%a");
        $arrDate = [];
        if($countDate == 0){
            $arrDate[] = $fromDate;
        }elseif ($countDate > 0){
            for ($i = 0; $i <= $countDate; $i++){
                $currenDate = date('Y-m-d', strtotime($fromDate. "+ " .$i. "days"));
                $arrDate[] = $currenDate;
            }
        }
        return $arrDate;
    }
}
