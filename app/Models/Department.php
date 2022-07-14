<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Kyslik\ColumnSortable\Sortable;

class Department extends Model
{
    use Notifiable, Sortable;
    protected $table = 'department';

    protected $guarded = [];
    protected $fillable = [
        'employee_id',
        'zoho_id',
        'department_code',
        'department_name',
        'email',
        'department_lead_id',
        'department_lead_name',
        'department_lead_code',
        'added_by',
        'department_parent_name',
        'department_parent_id',
        'domain_id',
        'updated_at',
        'created_at'
    ];
    
}
