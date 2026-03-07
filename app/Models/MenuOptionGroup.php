<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuOptionGroup extends Model
{
    use HasFactory;

    protected $table = 'menu_option_groups';

    protected $fillable = ['menu_id', 'name', 'selection_type', 'sort_order'];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function choices()
    {
        return $this->hasMany(MenuChoice::class, 'group_id')->orderBy('sort_order');
    }
}
