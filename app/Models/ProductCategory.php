<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = ['name', 'photo', 'photo_url'];
    public function products(){
        return $this->hasMany(Product::class);
    }
}
