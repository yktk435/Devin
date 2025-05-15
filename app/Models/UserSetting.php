<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'monthly_working_hours',
        'exclude_keywords',
    ];

    protected $casts = [
        'monthly_working_hours' => 'float',
    ];
}
