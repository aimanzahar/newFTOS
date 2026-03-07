<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuChoice extends Model
{
    use HasFactory;

    protected $table = 'menu_choices';

    protected $fillable = ['group_id', 'name', 'price', 'quantity', 'sort_order', 'status'];

    public function group()
    {
        return $this->belongsTo(MenuOptionGroup::class, 'group_id');
    }
}
