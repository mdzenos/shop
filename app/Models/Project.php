<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Kyslik\ColumnSortable\Sortable;

class Project extends Model
{
    use Notifiable, Sortable;
    protected $table = 'projects';


}
