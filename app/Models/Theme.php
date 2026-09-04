<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = ['name', 'color_background', 'color_primary', 'color_secondary'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
