<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{

    protected $table = 'domain';

    public function form()
    {
        return $this->hasMany(ZohoForm::class, 'domain_id', 'id');
    }

}
