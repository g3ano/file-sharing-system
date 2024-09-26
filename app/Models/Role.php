<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug',
    ];

    public static $validGlobalRoles = [
        RoleEnum::ADMIN->value, RoleEnum::MANAGER->value, RoleEnum::VIEWER->value,
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
            null,
            'id',
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
