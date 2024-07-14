<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'user_id',
    //     'access_token',
    //     'refresh_token',
    //     'token_expiry',
    // ];
    protected $guarded = [];

    protected $dates = ['token_expiry'];
}
