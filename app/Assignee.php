<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Assignee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'assigned_to',
    ];

    protected $hidden = [];

    protected $casts = [];

    /**
     * Scope a query to only include email addresses with a given assignment.
     *
     * @param Builder $query
     * @param string $id
     * @return Builder
     */
    public function scopeEmail($query, $id)
    {
        return $query->select('email')->where('assigned_to', $id);
    }

    public function project()
    {
        return $this->hasOne('App\Project', 'id');
    }
}
