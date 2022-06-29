<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Product([
            'name'=>$row['name'],
            'description'=>$row['description'],
            'content'=>$row['content'],
            'menu_id'=>$row['menu_id'],
            'price'=>$row['price'],
            'price_sale'=>$row['price_sale'],
            'active'=>$row['active'],
            'thumb'=>$row['thumb']
        ]);
    }
}
