<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(RoleUser::class)
            ->withTimestamps();
    }

    public function workspaces()
    {
        return $this->hasManyThrough(
            Workspace::class,
            RoleUser::class,
            'role_id', //Field that references Role on pivot
            'id', //Field that identifies workspace on Workspace
            'id', //Field to match against pivot Role foreign key
            'workspace_id', //Field that identifies workspace on pivot
        );
    }

    public function projects()
    {
        return $this->hasManyThrough(
            Project::class,
            RoleUser::class,
            'role_id', //Field that references Role on pivot
            'id', //Field that identifies workspace on Workspace
            'id', //Field to match against pivot Role foreign key
            'project_id', //Field that identifies workspace on pivot
        );
    }
}
