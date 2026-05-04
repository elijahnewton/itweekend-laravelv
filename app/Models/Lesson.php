<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'title',
        'slug',
        'content_html',
        'code_example',
        'code_language',
        'video_url',
        'order_index',
        'estimated_minutes',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function course(): BelongsTo
    {
        return $this->chapter->course();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }
}
