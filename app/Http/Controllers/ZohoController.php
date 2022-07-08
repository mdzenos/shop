<?php

namespace App\Http\Controllers;

use App\Models\RefreshToken;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;


class ZohoController extends Controller
{
    protected $config, $domain, $user;

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    public function randomToken($callBack = 0)
    {

        $config     = $this->config;
        $domainId   = $config['id'];

        $redis = Redis::connection();
        $arrTmp = $redis->get('zoho_token');

        if(empty($arrTmp)){
            $arrData = $this->getListRefreshToken();
            $redis->set('zoho_token', json_encode($arrData));
        }else{
            $arrData = json_decode($arrTmp, true);

        }

        $response = [];

        if(array_key_exists($domainId, $arrData)){

            $currentDate = date("d-m-Y H:i:s");

            foreach ($arrData[$domainId] as $key => $item){
                if(isset($item['last_time']) && $item['last_time'] != ''){
                    $diffSecond = strtotime($currentDate) - strtotime($item['last_time']);
                }else{
                    $diffSecond = 3000;
                }

                if($diffSecond >= 3000){
                    //Nếu có sựu thay đổi token thì xóa redis đi
                    $redis->del('zoho_token');

                    $tokenExist = RefreshToken::where('domain_id', '=', $domainId)->where('zoho_token', '=', $item['zoho_token'])->where('status', '=', 1)->first();

                    if(!empty($tokenExist)){

                        //Lấy token mới
                        $body['refresh_token']  = $tokenExist->refresh_token;
                        $body['client_id']      = $tokenExist->client_id;
                        $body['client_secret']  = $tokenExist->client_secret;
                        $body['grant_type']     = $tokenExist->grant_type;

                        $arrTokenZoho = $this->getTokenZoho($body);

                        if(isset($arrTokenZoho['access_token'])){

                            $arrUpdate = [
                                'zoho_token' => $arrTokenZoho['access_token'],
                                'last_time' => date('Y-m-d H:i:s')
                            ];
                            RefreshToken::where('domain_id', '=', $domainId)->where('id', '=', $tokenExist->id)->update($arrUpdate);

                            $response[] = $arrTokenZoho['access_token'];
                        }else{
                            $response[] = $item['zoho_token'];
                        }
                    }else{
                        $this->logError(true, '', 'Call Back : randomToken : DB empty ===', $item);
                        if($callBack == 0){
                            $this->randomToken(1);
                        }
                    }
                }else{
                    $response[]  = $item['zoho_token'];
                }
            }
        }

        return $response;
    }

    public function getListRefreshToken()
    {
        $arrData = RefreshToken::where('status', '=', 1)->get();
        $arrToken = [];
        if(!empty($arrData)){
            foreach ($arrData as $key => $item){

                $lastTime       = $item->last_time;
                $currentDate    = date("d-m-Y H:i:s");
                if(isset($item->last_time) && $item->last_time != ''){
                    $diffSecond = strtotime($currentDate) - strtotime($item->last_time);
                }else{
                    $diffSecond = 3000;
                    $lastTime   = $currentDate;
                }

                if($diffSecond >= 3000){

                    //Lấy token mới
                    $body['refresh_token']  = $item->refresh_token;
                    $body['client_id']      = $item->client_id;
                    $body['client_secret']  = $item->client_secret;
                    $body['grant_type']     = $item->grant_type;

                    $response = $this->getTokenZoho($body);
                    if(isset($response['access_token'])){

                        $arrUpdate = [
                            'zoho_token' => $response['access_token'],
                            'last_time' => date('Y-m-d H:i:s')
                        ];
                        RefreshToken::where('domain_id', '=', $item->domain_id)->where('id', '=', $item->id)->update($arrUpdate);

                        $arrToken[$item->domain_id][$key]['zoho_token']   = $response['access_token'];
                        $arrToken[$item->domain_id][$key]['last_time']    = $arrUpdate['last_time'];
                    }else{
                        $this->logError(true, 'monitor', 'ERROR : getTokenZoho : ', $response);
                    }
                }else{
                    $arrToken[$item->domain_id][$key]['zoho_token']   = $item->zoho_token;
                    $arrToken[$item->domain_id][$key]['last_time']    = $lastTime;
                }
            }
        }
        return $arrToken;
    }


    public function getTokenZoho($data = [])
    {
        $url = 'https://accounts.zoho.com/oauth/v2/token';
        $url .= '?refresh_token='.$data['refresh_token'].'&client_id='.$data['client_id'].'&client_secret='.$data['client_secret'].'&grant_type='.$data['grant_type'];

        $client     = new \GuzzleHttp\Client();

        $response = $client->request("POST", $url, $data);
        $statusCode = $response->getStatusCode();

        $data = json_decode($response->getBody(), true);
        return $data;
    }

    public function callZoho($action = '', $parameter = [], $convert = true, $method = 'POST', $callback = 0, $ip = '')
    {
        $arrIp = [
            env('INTERFACE_1'),
            env('INTERFACE_2'),
            env('INTERFACE_3'),
            env('INTERFACE_4'),
        ];

        $ip = $arrIp[array_rand($arrIp)];
        $arrToken = $this->randomToken();
        if(empty($arrToken)){
            $this->logError(true,'',"ERROR : Token empty");
            return [];
        }

        $zohoToken = $arrToken[array_rand($arrToken)];
        $body['headers'] = [
            'Authorization' => 'Bearer ' . $zohoToken,
            'Accept' => 'application/json',
        ];

        $domain      = isset($this->config['name'])?$this->config['name']:'';

        $env = config('app.env');

        if($env == 'local'){
            $client     = new \GuzzleHttp\Client();
        }else{
            $client     = new \GuzzleHttp\Client([
                'curl' => [
                    CURLOPT_INTERFACE => $ip
                ]
            ]);
        }

        $arrParam=[];
        if(!empty($parameter)){
            foreach ($parameter as $key => $value){
                $arrParam[$key] = $value;
            }
        }

        $url = $domain . '/api/' .$action;

        if(strtolower($method) == 'get'){
            $typeParam = 'query';
        }else{
            $typeParam = 'form_params';
        }

        $body[$typeParam]               = $arrParam;

        $response = $client->request($method, $url, $body);

       // $statusCode = $response->getStatusCode();
        $data = json_decode($response->getBody(), true);
        if($convert){
            $result = $this->convertZohoBody($data, $action);
        }else{
            $result = $data;
        }

        //callback lại khi gặp lỗi /Internal Server Error Occured/ khi gọi api zoho
        if(isset($result['errors']['code'])){
            $this->logError(true,'',"ERROR callZoho === Action : ". $action ." === IP : ". $ip. ' === TOKEN : '.$zohoToken, $result);
        }

        return $result;
    }

    /**
     * @function callZohoConnect
     * @description API-ZohoConnect push notify
     * @document https://www.zoho.com/connect/api/create-announcement.html
     *
     * @param string $action
     * @param array $parameter
     * @return array $response
     */
    public function callZohoConnect($action = '', $parameter = [])
    {
        $tokens = $this->randomToken();

        if(empty($tokens)){
            $this->logError(true, '' , "ERROR : Token empty from callZohoConnect");
            return [];
        }

        $url = 'https://connect.zoho.com' . $action;

        //Token zohopulse.feedList.CREATE
        $response = Http::asForm()
        ->withToken($tokens[0])
        ->post($url, $parameter);

        return $response->json();
    }

    /**
     * @function callZohoSyncDateOfBirthAndDateOfDay
     * @description API-ZohoConnect update info user
     *
     * @param string $action
     * @param array $parameter
     * @param array $customField
     * @return array $response
     */
    public function callZohoSyncDateOfBirthAndDateOfDay($action = '', $parameter = [], $customField=[])
    {
        $tokens = $this->randomToken();

        if(empty($tokens)){
            $this->logError(true, '' , "ERROR : Token empty from callZohoConnect");
            return [];
        }

        $url = 'https://connect.zoho.com' . $action . '?' . http_build_query($parameter);

        $customFieldParams = [];
        if (count($customField) > 0) {
            $customFieldParams['customFields'] = json_encode($customField);
        }

        //Token zohopulse.userDetail.UPDATE
        $response = Http::asForm()
            ->withToken($tokens[1])
            ->post($url, $customFieldParams);

        return $response->json();
    }

    /**
     * @function addUserToGroup
     * @description API-ZohoConnect add user to group
     *
     * @param string $action
     * @param array $parameter
     * @param array $tokenId
     * @return array $response
     */
    public function callAPIZohoConnect($action = '', $parameter = [], $tokenId = 0)
    {
        $tokens = $this->randomToken();
        if(empty($tokens)){
            $this->logError(true, '' , "ERROR : Token empty from callZohoConnect");
            return [];
        }

        $url = 'https://connect.zoho.com' . $action;

        $response = Http::asForm()
            ->withToken($tokens[$tokenId])
            ->post($url, $parameter);

        return $response->json();
    }

    protected function convertZohoBody($body = [], $type = '')
    {
        if(empty($body)){
            return [];
        }

        $response = [];
        if(isset($body['response']['status']) && $body['response']['status'] == 0){

            if(strpos($type, 'getRecordByID') == true){

                $resault = isset($body['response']['result'][0])?$body['response']['result'][0]:[];
                if(!empty($resault)){
                    foreach ($resault as $r => $item){
                        if($r === 'tabularSections'){
                            $response['tabularSections'] = $item;
                        }else{
                            if(is_array($item)){
                                if(!empty($item)){
                                    foreach ($item as $field => $val){
                                        $response[$field] = $val;
                                    }
                                }
                            }else{
                                $response[$r] = $item;
                            }
                        }
                    }
                }
            }elseif(strpos($type, 'getDataByID') == true){
                $response = isset($body['response']['result'][0])?$body['response']['result'][0]:[];
            }
            elseif(strpos($type, 'getRecordCount') == true){

                $response = isset($body['response']['result']['RecordCount'])?$body['response']['result']['RecordCount']:[];
            }elseif (strpos($type, 'components') == true){
                foreach ($body['response']['result'] as $value) {
                    if (isset($value['tabularSections'])) {
                        foreach ($value['tabularSections'] as $key => $item) {
                            foreach ($item as $keyI => $valueI) {
                                if ($keyI !== "sectionId") {
                                    $words = $keyI;
                                    foreach ($valueI as $last_value) {
                                        foreach ($last_value as $key_last => $value_last) {
                                            $response[$words]['sectionId'] = $item['sectionId'];
                                            if ($key_last == 'comptype' && $value_last == 'Picklist') {
                                                $response[$words] = $last_value['Options'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif (strpos($type, 'getHolidays') == true){
                return $body['response']['result'];
            }else{

                if(!empty($body['response']['result'])){
                    if(isset($body['response']['result']['pkId'])){
                        return $body['response'];
                    }else{
                        foreach ($body['response']['result'] as $data){
                            if((!empty($data))){
                                foreach ($data as $key => $item){
                                    if(isset($item[0])){
                                        $response[] = $item[0];
                                    }else{
                                        $response[] = $item;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }elseif(isset($body['response']['status']) && $body['response']['status'] == 1){
            $response = $body['response'];
        }else{
            if(strpos($type, 'getRegularizationRecords') == true){
                $response = $body['result'];
            }else{
                $response = $body;
            }
        }
        return $response;
    }

    public function getSectionForm($form = '', $version = 2, $convert = true)
    {
        $body       = [];
        if($version){
            $body['version'] = 2;
        }
        $resault = $this->callZoho($form,  $body, $convert);
        return $resault;
    }

    public function getRecordByID($id = '', $form = '', $fileName = 'onsite')
    {
        $body = [];
        if($id){
            $body['recordId'] = $id;
        }
        $response = $this->callZoho($form, $body, true);
        return $response;
    }

    public function getRecords($form = '', $body = [], $convert = true)
    {
        $response = $this->callZoho($form, $body, $convert);
        return $response;
    }

    public function createdOrUpdated($form = '', $data = [], $tabular = [], $zohoId = '',  $formatDate = '')
    {
        $body = [];
        if($zohoId){
            $body['recordId'] = $zohoId;
        }
        if(!empty($data)){
            $body['inputData'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        if(!empty($tabular)){
            $body['tabularData'] = json_encode($tabular, JSON_UNESCAPED_UNICODE);
        }
        if($formatDate){
            $body['dateFormat'] = $formatDate;
        }

        $resault = $this->callZoho($form, $body, true);
        return $resault;
    }

    public function deleteRecords($form = '', $body = [], $convert = true)
    {
        $response = $this->callZoho($form, $body, $convert);
        return $response;
    }

    public function existOnsiteRequestByCode($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['onsite_request']['getRecords'])?$this->config['onsite_request']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Onsite_ID', searchOperator: 'Is', searchText : " . "'" . $arrParam['onsite_code'] . "'" . "}";
        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existOnsiteExpense($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['onsite_expense']['getRecords'])?$this->config['onsite_expense']['getRecords']:'';

        $empId      = $this->convertEmp($arrParam);
        $onsiteFiledCode = 'Onsite_Expense1';

        $onsiteFiledCode = $this->config['onsite_expense']['auto_number_key'];

        $body['searchParams'] = "{searchField: 'employee_id', searchOperator: 'Contains', searchText : " . "'" . $empId . "'" . "} | {searchField: $onsiteFiledCode, searchOperator: 'Is', searchText : " . "'" . $arrParam['expense_code'] . "'" . "}";

        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existOnsiteExpenseByCode($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['onsite_expense']['getRecords'])?$this->config['onsite_expense']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'employee_id', searchOperator: 'Contains', searchText : " . "'" . $arrParam['emp_onsite'] . "'" . "} | {searchField: 'onsite_code', searchOperator: 'Is', searchText : " . "'" . $arrParam['onsite_code'] . "'" . "}";

        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existTicketAdmin($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['flight_ticket_expense']['getRecords'])?$this->config['flight_ticket_expense']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'employee_id', searchOperator: 'Is', searchText : " . "'" . $arrParam['emp_onsite'] . "'" . "}| {searchField: 'onsite_code', searchOperator: 'Is', searchText : " . "'" . $arrParam['onsite_code'] . "'" . "}";
        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existTicketDm($arrParam = [], $convert = true, $fileName = 'onsite')
    {
        $action     = isset($this->config['for_dm_flight_ticket_expense']['getRecords'])?$this->config['for_dm_flight_ticket_expense']['getRecords']:'';
        $empId      = $this->convertEmp($arrParam);

        $body['searchParams'] = "{searchField: 'employee_id', searchOperator: 'Is', searchText : " . "'" . $empId . "'" . "}| {searchField: 'onsite_code', searchOperator: 'Is', searchText : " . "'" . $arrParam['onsite_code'] . "'" . "}";

        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function getTicketDmByFlightCode($flightCode = [], $convert = true, $fileName = 'onsite')
    {
        $action     = isset($this->config['for_dm_flight_ticket_expense']['getRecords'])?$this->config['for_dm_flight_ticket_expense']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'flight_code1', searchOperator: 'Contains', searchText : " . "'" . $flightCode . "'" . "}";
        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }
    public function existTicketDmByAdminCode($adminCode = '', $convert = true, $fileName = 'onsite')
    {
        $action     = isset($this->config['for_dm_flight_ticket_expense']['getRecords'])?$this->config['for_dm_flight_ticket_expense']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'flight_admin_code', searchOperator: 'Is', searchText : " . "'" .$adminCode . "'" . "}";

        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existTrackingPaymentByExpenseCode($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['tracking_the_advance_payment_actions']['getRecords'])?$this->config['tracking_the_advance_payment_actions']['getRecords']:'';
        $empId      = $this->convertEmp($arrParam);

        $filedCode = 'expense_code';

        $body['searchParams'] = "{searchField: 'onsiter', searchOperator: 'Contains', searchText : " . "'" . $empId . "'" . "} | {searchField: $filedCode, searchOperator: 'Is', searchText : " . "'" . $arrParam['expense_code'] . "'" . "}";
        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existTrackingPaymentByOnsiteCode($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['tracking_the_advance_payment_actions']['getRecords'])?$this->config['tracking_the_advance_payment_actions']['getRecords']:'';
        $empId      = $this->convertEmp($arrParam);

        $filedCode = 'onsite_id';

        $body['searchParams'] = "{searchField: 'onsiter', searchOperator: 'Contains', searchText : " . "'" . $empId . "'" . "} | {searchField: $filedCode, searchOperator: 'Is', searchText : " . "'" . $arrParam['onsite_code'] . "'" . "}";
        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existClearancePayment($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['onsite_advance_clearance1']['getRecords'])?$this->config['onsite_advance_clearance1']['getRecords']:'';
        $empId      = $this->convertEmp($arrParam);
        $body['searchParams'] = "{searchField: 'onsiter', searchOperator: 'Contains', searchText : " . "'" . $empId . "'" . "} | {searchField: 'advance_payment_code', searchOperator: 'Is', searchText : " . "'" . $arrParam['advance_payment_code'] . "'" . "}";

        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function searchAdvancePayment($arrParam = [], $convert = true)
    {
        $action     = isset($this->config['onsite_advance_payment_request']['getRecords'])?$this->config['onsite_advance_payment_request']['getRecords']:'';
        $empId      = $this->convertEmp($arrParam);
        $body['searchParams'] = "{searchField: 'onsiter', searchOperator: 'Contains', searchText : " . "'" . $empId . "'" . "}| {searchField: 'expense_code', searchOperator: 'Is', searchText : " . "'" . $arrParam['expense_code'] . "'" . "}";

        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function existListingResourceByDateProject($date = '', $project = '', $convert = true)
    {
        $action     = isset($this->config['resource_allocation']['getRecords'])?$this->config['resource_allocation']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'month', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "}| {searchField: 'project', searchOperator: 'Is', searchText : " . "'" . $project . "'" . "}";
        $response = $this->callZoho($action, $body, $convert);
        return $response;
    }

    public function getleaveRecord($id = '', $form = '', $fileName = 'onsite')
    {
        $body = [];
        if($id){
            $body['userId'] = $id;
        }

        $response = $this->callZoho($form, $body, true);
        return $response;
    }

    public function deleteRecord($id = null,$formName = '')
    {
        $url    = 'deleteRecords';
        $body['recordIds'] = $id;
        $body['formLinkName'] = $formName;
        $response = $this->callZoho($url, $body);
        return $response;
    }

    public function searchDetailByListingId($listingId = null, $index = '')
    {
        $action     = isset($this->config['resource_allocation_detail']['getRecords'])?$this->config['resource_allocation_detail']['getRecords']:'';
        $body['searchParams'] = "{searchField: 'listing_id', searchOperator: 'Is', searchText : " . "'" . $listingId . "'" . "}";
        $body['sIndex'] = $index;
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchResourceLittingByMonth($month ='')
    {
        $action     = isset($this->config['resource_allocation']['getRecords'])?$this->config['resource_allocation']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'month', searchOperator: 'Is', searchText : " . "'" . $month . "'" . ",}";

        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchLeave($monthOfYear,$empCode)
    {
        $from = '01-'.$monthOfYear;
        $date = date('d-m-Y',strtotime($from));
        $arrMonthYear = explode('-',$date);
        $month = isset($arrMonthYear[1])?$arrMonthYear[1]:'';
        $year = isset($arrMonthYear[2])?$arrMonthYear[2]:'';
        $dayOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to = $dayOfMonth.'-'.$monthOfYear;
        $between =$from.';'.$to;

        $action     = isset($this->config['leave']['getRecords'])?$this->config['leave']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'From', searchOperator: 'Between',searchCriteria: 'AND', searchText : " . "'" . $between . "'" . ",}|{searchField: 'Employee_ID', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "}";
        $body['searchParams'] = "{searchField: 'To', searchOperator: 'Between',searchCriteria: 'AND', searchText : " . "'" . $between . "'" . ",}|{searchField: 'Employee_ID', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchOverViewByDateCode($date,$employee) {
        $action     = isset($this->config['resource_allocation_overview']['getRecords'])?$this->config['resource_allocation_overview']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'employee', searchOperator: 'Contains', searchText : " . "'" . $employee . "'" . "}| {searchField: 'date', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchListingByProjectId($code = '') {
        $action     = isset($this->config['resource_allocation']['getRecords'])?$this->config['resource_allocation']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'project', searchOperator: 'Is', searchText : " . "'" . $code . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchOverViewByMonth($month = '',$index = '')
    {
        $action     = isset($this->config['resource_allocation_overview']['getRecords'])?$this->config['resource_allocation_overview']['getRecords']:'';

        $body       = [];
        $fromDate   = '1-'.$month;
        $toDate     = date("t-M-Y", strtotime($fromDate));
        $between    = $fromDate . ';' . $toDate;

        $body['searchParams'] = "{searchField: 'date', searchOperator: 'Between',searchCriteria: 'AND', searchText : " . "'" . $between . "'" . "}";
        $body['sIndex'] = $index;
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchOverViewByMonthEmployee($month = '', $employee = '')
    {
        $action     = isset($this->config['resource_allocation_overview']['getRecords'])?$this->config['resource_allocation_overview']['getRecords']:'';

        $body       = [];
        $fromDate   = '1-'.$month;
        $toDate     = date("t-M-Y", strtotime($fromDate));
        $between    = $fromDate . ';' . $toDate;

        $body['searchParams'] = "{searchField: 'date', searchOperator: 'Between',searchCriteria: 'AND', searchText : " . "'" . $between . "'" . "} | {searchField: 'employee', searchOperator: 'Contains', searchText : " . "'" . $employee . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function checkExistResourceAllocationOverview ($employee = '',$date = '')
    {
        $action     = isset($this->config['resource_allocation_overview']['getRecords'])?$this->config['resource_allocation_overview']['getRecords']:'';
        $body       = [];
        $body['searchParams'] = "{searchField: 'date', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "} | {searchField: 'employee', searchOperator: 'Contains', searchText : " . "'" . $employee . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function getRecordCount($form = '', $body = [], $convert = true)
    {

        $response = $this->callZoho($form, $body, $convert);
        return $response;
    }

    public function getEntryAttendanceByEmployee($action = '', $arrParam)
    {
        $employeeId = $arrParam['empId'];

        //convert account_id nếu nhỏ hơn 4 ký tự
        if(strlen($arrParam['empId']) < 4){
            $employeeId = str_pad($arrParam['empId'], 4, '0', STR_PAD_LEFT );
        }

        $body = [
            "date" => $arrParam['date'],
            "empId" => $employeeId
        ];

        $data = $this->callZoho($action, $body, true);
        return $data;
    }


    public function getAttendanceByEmployee($form = '', $body = [], $convert = true)
    {
        //Lấy attendance trong kỳ lương
        $bodyAttendance = [
            'sdate' => date('d-M-Y', strtotime($body['firstPunch'])),
            'edate' => date('d-M-Y', strtotime($body['lastPunch'])),
            'empId' => $body['empCode'],
        ];

        $response = $this->callZoho($form, $bodyAttendance, $convert);
        return $response;
    }

    public function bulkImport($form = '', $body = [], $convert = true)
    {
        $bodyAttendance = [
            'dateFormat' => "dd/MM/yyyy HH:mm:ss",
            'data' => json_encode($body, JSON_UNESCAPED_UNICODE),
        ];

        $response = $this->callZoho($form, $bodyAttendance, $convert);
        return $response;
    }

    public function getStatusRegularization($form = '', $body = [])
    {

        $body['dateFormat'] = "yyyy-MM-dd";
        $response = $this->callZoho($form, $body, false, 'GET');
        return $response;
    }

    public function searchTimesheetDetail($empCode = '', $logTimesheetNo = '')
    {
        $action     = isset($this->config['report_timesheet_details']['getRecords'])?$this->config['report_timesheet_details']['getRecords']:'';
        $body['searchParams'] = "{searchField: 'Employee', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "} | {searchField: 'Log_Timesheet_No', searchOperator: 'Is', searchText : " . "'" . $logTimesheetNo . "'" . "}";

        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchLogTimesheetPunch($empCode = '', $date = '')
    {
        $action     = isset($this->config['time_log']['getRecords'])?$this->config['time_log']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Emp_info', searchOperator: 'Contains', searchText : " . "'" . $empCode. "'" . "}| {searchField: 'Date', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "} ";

        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchLogTimesheetSum($empCode = '', $date = '', $project = '')
    {
        $action     = isset($this->config['time_log']['getRecords'])?$this->config['time_log']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Emp_info', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "}| {searchField: 'Date', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "} | {searchField: 'Project', searchOperator: 'Contains', searchText :" . "'" . $project . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchLogOvertimePunch($empCode = '', $date = '')
    {
        $action     = isset($this->config['overtime_registration']['getRecords'])?$this->config['overtime_registration']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Emp_info', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "}| {searchField: 'Date', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "}";

        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchLogOvertimeSum($empCode = '', $date = '', $project = '')
    {
        $action     = isset($this->config['overtime_registration']['getRecords'])?$this->config['overtime_registration']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Emp_info', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "}| {searchField: 'Date', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "} | {searchField: 'Project', searchOperator: 'Contains', searchText :" . "'" . $project . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function existTimesheetPunch($empCode = '', $date = '', $code = '')
    {
        $action     = isset($this->config['report_timesheet_vs_punch_time']['getRecords'])?$this->config['report_timesheet_vs_punch_time']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Employee', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "} | {searchField: 'Date1', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "} | {searchField: 'code', searchOperator: 'Is', searchText : " . "'" . $code . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function betweenTimesheetPunch($empCode = '', $fromDate = '', $toDate = '')
    {
        $action     = isset($this->config['report_timesheet_vs_punch_time']['getRecords'])?$this->config['report_timesheet_vs_punch_time']['getRecords']:'';

        $between    = $fromDate . ';' . $toDate;
        $body['searchParams'] = "{searchField: 'Employee', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "} | {searchField: 'Date1', searchOperator: 'Between', searchText : " . "'" . $between . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchLeaveWorking($employeeId = '', $fromDate = '', $toDate = '')
    {
        $action     = isset($this->config['leave']['getRecords'])?$this->config['leave']['getRecords']:'';

        $body       = [];
        $between    = $fromDate . ';' . $toDate;
        $body['searchParams'] = "{searchField: 'From', searchOperator: 'Between', searchText : " . "'" . $between . "'" . "} | {searchField: 'Employee_ID', searchOperator: 'Like', searchText : " . "'" . $employeeId . "'" . "} ";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function existTimesheetSummary($empCode = '', $date = '', $project = '')
    {
        $action     = isset($this->config['report_timesheet']['getRecords'])?$this->config['report_timesheet']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Employee', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "} | {searchField: 'Date1', searchOperator: 'Is', searchText : " . "'" . $date . "'" . "} | {searchField: 'Project', searchOperator: 'Contains', searchText :" . "'" . $project . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchTimesheetDetailNumber($empCode = '',$logTimesheetNo = '')
    {
        $action     = isset($this->config['report_timesheet_details']['getRecords'])?$this->config['report_timesheet_details']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Employee', searchOperator: 'Contains', searchText : " . "'" . $empCode . "'" . "} | {searchField: 'Log_Timesheet_No', searchOperator: 'Is', searchText : " . "'" . $logTimesheetNo . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }
    public function searchServiceEvaluation($requestId = '')
    {
        $action     = isset($this->config['service_evaluation']['getRecords'])?$this->config['service_evaluation']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'request_ID' , searchOperator: 'Is', searchText : " . "'" . $requestId . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchPurchaseRequest($requestId = '')
    {
        $action     = isset($this->config['purchasing_request']['getRecords'])?$this->config['purchasing_request']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Request_ID' , searchOperator: 'Is', searchText : " . "'" . $requestId . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchAdvanceRequest($requestId = '')
    {
        $action     = isset($this->config['advance_payment_request']['getRecords'])?$this->config['advance_payment_request']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'Request_ID' , searchOperator: 'Is', searchText : " . "'" . $requestId . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchVendorRequest($requestId = '')
    {
        $action     = isset($this->config['process_for_purchasing_request']['getRecords'])?$this->config['process_for_purchasing_request']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'request_id' , searchOperator: 'Is', searchText : " . "'" . $requestId . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    public function searchClearanceRequest($requestId = '')
    {
        $action     = isset($this->config['reimbursement_request']['getRecords'])?$this->config['reimbursement_request']['getRecords']:'';

        $body['searchParams'] = "{searchField: 'advance_payment_request_ID' , searchOperator: 'Is', searchText : " . "'" . $requestId . "'" . "}";
        $response = $this->callZoho($action, $body, true);
        return $response;
    }

    /**
     * @function callAPIZoho
     * @description get list task
     *
     * @param string $action
     * @param array $parameter
     * @param array $tokenId
     * @return array $response
     */
    public function callAPIZoho($action = '', $parameter = [], $tokenId = 0)
    {
        $tokens = $this->randomToken();
        if(empty($tokens)){
            $this->logError(true, 'task' , "ERROR : Token empty from Form");
            return [];
        }

        $domain = isset($this->config['name'])?$this->config['name']:'';

        $url = $domain . '/people/api/' . $action . '?' . http_build_query($parameter);

        $response = Http::withToken($tokens[$tokenId])
            ->post($url, $parameter);

        return $response->json();
    }

    /**
     * @function deleteRecordsWithForm
     * @description delete task with recordIds
     *
     * @param string $action
     * @param array $parameter
     * @param array $tokenId
     * @return array $response
     */
    public function deleteRecordsWithForm($parameter = [], $tokenId = 0)
    {
        $tokens = $this->randomToken();
        if(empty($tokens)){
            $this->logError(true, 'task' , "ERROR : Token empty from Form");
            return [];
        }

        $domain = isset($this->config['name'])?$this->config['name']:'';

        $url = $domain . '/api/deleteRecords';

        $response = Http::asForm()->withToken($tokens[$tokenId])
            ->post($url, $parameter);

        return $response->json();
    }

    /**
     * @document https://help.zoho.com/portal/en/kb/connect/webhooks/articles/advanced-message-formatting-in-incoming-webhooks#How_to_post_a_message_with_an_embedded_image
     *
     * @return $response
     */
    public function callIncomingWebhook($parameter) {
        $url = env('URL_INCOMING_WEBHOOK', '');

        $response = Http::asForm()
        ->post($url, $parameter);

        return $response->json();
    }

    public function getRecordRecruit($form = '', $body = [], $convert = true)
    {
        $response = $this->callZohoRecruit($form, $body, $convert);
        return $response;
    }

    public function callZohoRecruit($action = '', $parameter = [], $convert = true, $method = 'POST', $callback = 0, $ip = '')
    {
        $arrIp = [
            env('INTERFACE_1'),
            env('INTERFACE_2'),
            env('INTERFACE_3'),
            env('INTERFACE_4'),
        ];

        $ip = $arrIp[array_rand($arrIp)];
        $arrToken = $this->randomToken();
        if(empty($arrToken)){
            $this->logError(true,'',"ERROR : Token empty");
            return [];
        }

        $zohoToken = $arrToken[array_rand($arrToken)];
        $body['headers'] = [
            'Authorization' => 'Bearer ' . $zohoToken,
            'Accept' => 'application/json',
        ];

        $domain      = isset($this->config['name'])?$this->config['name']:'';

        $env = config('app.env');

        if($env == 'local'){
            $client     = new \GuzzleHttp\Client();
        }else{
            $client     = new \GuzzleHttp\Client([
                'curl' => [
                    CURLOPT_INTERFACE => $ip
                ]
            ]);
        }

        if(!empty($parameter)){
            foreach ($parameter as $key => $value){
                $arrParam[$key] = $value;
            }
        }

        $url = $domain . '/recruit/' .$action;

        if(strtolower($method) == 'get'){
            $typeParam = 'query';
        }else{
            $typeParam = 'form_params';
        }

        $body[$typeParam] = $arrParam;
        $response = $client->request($method, $url, $body);
        $data = json_decode($response->getBody(), true);
        if($convert){
            $result = $this->convertZohoBody($data, $action);
        }else{
            $result = $data;
        }
        //callback lại khi gặp lỗi /Internal Server Error Occured/ khi gọi api zoho
        if(isset($result['errors']['code'])){
            $this->logError(true,'',"ERROR callZoho === Action : ". $action ." === IP : ". $ip. ' === TOKEN : '.$zohoToken, $result);
        }

        return $result;
    }

    public function downloadPhoto($url)
    {
        $arrToken = $this->randomToken();
        if(empty($arrToken)){
            $this->logError(true,'',"ERROR : Token empty");
            return [];
        }
        $zohoToken = $arrToken[array_rand($arrToken)];
        if($zohoToken == ''){
            $this->logError(true, '', 'ERROR : Token empty === END ');
            return [];
        }
        $body['headers'] = [
            'Authorization' => 'Bearer ' . $zohoToken,
            'Accept' => 'application/json',
        ];
        $client     = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url, $body);

        return $response;
    }

}
