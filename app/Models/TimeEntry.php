<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'redmine_id',
        'user_id',
        'user_name',
        'issue_id',
        'issue_subject',
        'hours',
        'spent_on',
        'comments',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'spent_on' => 'date',
        'hours' => 'float',
    ];
}
