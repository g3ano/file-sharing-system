<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ["name", "description", "user_id"];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(UserWorkspace::class)
            ->withTimestamps();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function abilities(): MorphMany
    {
        return $this->morphMany(Ability::class, "entity");
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function files(): HasManyThrough
    {
        return $this->hasManyThrough(File::class, Project::class);
    }
}
