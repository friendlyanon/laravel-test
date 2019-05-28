<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model {
    const states = [
        'waiting',
        'progressing',
        'done',
    ];

    protected $fillable = [
        'name',
        'state',
        'description',
    ];

    protected $hidden = [];

    protected $casts = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignees() {
        return $this->hasMany('App\Assignee', 'assigned_to');
    }

    /**
     * @return int
     */
    public function getAssignedUserCount() {
        return Assignee::where('assigned_to', $this->id)->count();
    }

    public function getAssignedUsers() {
        return Assignee::select('name', 'email')->where('assigned_to', $this->id)->get();
    }
}
