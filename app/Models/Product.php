<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $table = "products";
    use HasFactory;
    protected $fillable = [
        'name','description','content','menu_id','price','price_sale','active','thumb'
    ];

    public function menu()
    {
        return $this->hasOne(Menu::class, 'id', 'menu_id')
            ->withDefault(['name' => '']);
    }

    //Export Product
    public static function getProduct(){
        $records = DB::table('products')
        ->select('id','name','description','content','menu_id',
                    'price','price_sale','active','thumb')
        ->get()->toArray();
        return $records;
    }
}
