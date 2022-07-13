<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Kyslik\ColumnSortable\Sortable;

class Project extends Model
{
    use Notifiable, Sortable;
    protected $table = 'projects';

    protected $fillable = [
        'zoho_id',
        'project_code',
        'project_name',
        'project_manager_emp_code',
        'project_manager_emp_zoho_id',
        'div_project_zoho_id',
        'div_project_name',
        'div_manager_emp_id',
        'status',
        'project_user',
        'start_date',
        'end_date',
        'project_type',
        'project_size',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];
}
