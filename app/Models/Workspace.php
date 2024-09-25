<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'user_id',
    ];

    public static $validRoles = [
        RoleEnum::MANAGER->value, RoleEnum::EDITOR->value, RoleEnum::VIEWER->value,
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
