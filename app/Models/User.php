<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory;
    protected $guarded =[];

    protected $hidden = ['password'];
    protected function Password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => bcrypt($value),
        );
    }
}
