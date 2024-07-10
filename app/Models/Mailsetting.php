<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mailsetting extends Model
{
    use HasFactory;
    protected $guarded =[];
    protected $hidden = ['mail_password'];
}
