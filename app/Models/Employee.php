<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Kyslik\ColumnSortable\Sortable;

use Illuminate\Support\Facades\DB;

class Employee extends Authenticatable
{
    use Notifiable, Sortable;
    protected $key;

    protected $table = 'employees';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'emp_id',
        'active',
        'birthday_image',
        'date_of_birth',
        'full_name',
        'password',
        'is_send_birthday',
        'is_send_mail',
        'hanet_image',
        'hanet_status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role()
    {
        return $this->hasOne(Role::class,'id', 'role_id')->where('deleted', '=', 0);
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'zoho_id', 'division_id');
    }


    static function getEmployeeByCode($domainId = '', $code = '')
    {

        $keyRedis = 'employee_' . $domainId . '_' .$code;
        $redis      = Redis::connection();
        $arrData    = $redis->get($keyRedis);
        if(empty($arrData)){
            $arrData = Employee::where('domain_id', '=', $domainId)->where('code', '=', $code)->first();

            $response['code'] = 204;
            $response['msg'] = 'Data Empty';
            $response['data'] = [];
            if(!empty($arrData)){
                $response['code']   = 200;
                $response['msg']    = 'Data success';
                $response['data']   = $arrData->toArray();
                $response['data']['offer_salary'] = [];
                $response['data']['bonus'] =[];
                $response['data']['allowance'] = [];
                $response['data']['auto_punch'] = [];
            }
            $redis->set($keyRedis, json_encode($response));
        }else{
            $response = json_decode($arrData,true);
        }

        return $response;
    }

    static function getEmployeeCompanyByCode($code = '')
    {
        $arrData = Employee::where('code', '=', $code)->orderBy('domain_id','ASC')->groupBy('domain_id')->get();
        $response['code'] = 204;
        $response['msg'] = 'Data Empty';
        $response['data'] = [];
        if(!empty($arrData)){
            $response['code'] = 200;
            $response['msg'] = 'Data success';
            $response['data'] = $arrData->toArray();
        }
        return $response;
    }

    static function getAllDataEmployee($domainId = '', $param = [])
    {

        $response    = [];

        $limit          = isset($param['limit'])?$param['limit']:500;
        $offset         = isset($param['offset'])?$param['offset']:0;
        $active         = isset($param['active'])?$param['active']:'';
        $statusPayslip  = isset($param['status_payslip'])?$param['status_payslip']:'';

        $arrWhere[] = ['domain_id' , '=', $domainId];
        if($active !== ''){
            array_push($arrWhere, ['active', '=', $active]);
        }
        if($statusPayslip !== ''){
            array_push($arrWhere, ['status_payslip', '=', $statusPayslip]);
        }

        $countEmp = Employee::where($arrWhere)->count();
        if($countEmp <= 0){
            return [];
        }

        $num = ceil($countEmp / $limit);

        $key = 0;
        for($i = $offset; $i < $num; $i++) {

            $arrData    = Employee::where($arrWhere)->limit($limit)->offset($limit * $i)->orderBy('code','DESC')->orderBy('status_payslip','DESC')->get();
            if (!empty($arrData)) {
                foreach ($arrData as $item){
                    $response[$key]    = $item->toArray();
                    $key++;
                }
            }
        }
        return $response;
    }

    /**
     * relationship reporting_to_id
     */
    public function reportingTo()
    {
        return $this->belongsTo(self::class, 'reporting_to_id', 'zoho_id');
    }
    public static function getAllCodeEmployee(){
        $res =  DB::table('employees')->select('code')->where('active', 1)->get();
         return $res;
    }
    public static function getAllEmployee(){
        $res =  DB::table('employees')->select('code','job_title','full_name')->where('active', 1)->get();
         return $res;
    }
    public static function uploadAvatar(string $str){
        DB::table('employees')
              ->where('code','like' ,$str.'%')
              ->update(['update_avatar' => $str.'jpg']);
    }
}
