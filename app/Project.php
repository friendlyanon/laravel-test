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
        return $this->hasMany('App\Assignee', 'project_id');
    }

    /**
     * @return int
     */
    public function getAssignedUserCount()
    {
        return Assignee::where('project_id', $this->id)->count();
    }

    public function getAssignedUsers()
    {
        return Assignee::select('name', 'email')->where('project_id', $this->id)->get();
    }
}
