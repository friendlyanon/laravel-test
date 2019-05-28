<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Assignee extends Model {
    protected $fillable = [
        'name',
        'email',
        'assigned_to',
    ];

    protected $hidden = [];

    protected $casts = [];

    public function project() {
        return $this->hasOne('App\Project', 'id');
    }
}
