<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedmineUser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'redmine_id',
        'name',
    ];

    /**
     * Get the time entries for the user.
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'user_id', 'redmine_id');
    }
}
