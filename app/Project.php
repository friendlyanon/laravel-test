<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    const STATES = [
        'waiting',
        'progressing',
        'done',
    ];
    const FILLABLE = [
        'name',
        'state',
        'description',
    ];

    protected $fillable = self::FILLABLE;

    protected $hidden = [];

    protected $casts = [];

    /**
     * @return HasMany
     */
    public function assignees()
    {
        return $this->hasMany('App\Assignee', 'assigned_to');
    }

    /**
     * @return int
     */
    public function getAssignedUserCount()
    {
        return Assignee::where('assigned_to', $this->id)->count();
    }

    public function getAssignedUsers()
    {
        return Assignee::select('name', 'email')->where('assigned_to', $this->id)->get();
    }
}
