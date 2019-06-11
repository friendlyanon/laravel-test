<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Assignee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'project_id',
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
    public function scopeNameAndEmail($query, $id)
    {
        return $query->select(['email', 'name'])->where('project_id', $id);
    }

    public function project()
    {
        return $this->hasOne('App\Project', 'id');
    }
}
