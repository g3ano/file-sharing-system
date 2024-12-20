<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "user_id",
        "project_id",
        "name",
        "extension",
        "type",
        "size",
        "path",
        "hash",
    ];
    public static $savePath = "uploads";
    public static $allowedFileTypes = [
        "docx",
        "pdf",
        "png",
        "jpeg",
        "jpg",
        "zip",
        "txt",
    ];
    public static $maxFileSize = 76800; // 75MB

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
