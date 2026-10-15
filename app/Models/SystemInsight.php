<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemInsight extends Model
{
    use HasUuids;

    protected $fillable = [
        'source_key',
        'title',
        'content',
        'level',
        'target',
        'action_url',
        'action_label',
        'notification_id',
        'last_seen_at',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function getLevelColorAttribute(): string
    {
        return match($this->level) {
            'CRITICAL' => 'bg-destructive/15 text-destructive border-destructive/25',
            'WARNING'  => 'bg-warning/15 text-warning-foreground border-warning/25',
            default    => 'bg-accent/15 text-accent-foreground border-accent/20',
        };
    }

    public function getLevelIconAttribute(): string
    {
        return match($this->level) {
            'CRITICAL' => 'alert-triangle',
            'WARNING'  => 'alert-circle',
            default    => 'info',
        };
    }

    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            'CRITICAL' => __('messages.insight_level_critical'),
            'WARNING'  => __('messages.insight_level_warning'),
            default    => __('messages.insight_level_info'),
        };
    }

    public function getTargetLabelAttribute(): string
    {
        $target = $this->target ?: 'general';
        $key = "messages.notification_target_{$target}";
        $translated = __($key);

        return $translated !== $key ? $translated : $target;
    }

    public function getActionLabelTextAttribute(): string
    {
        $label = $this->action_label ?: 'messages.open_related_page';

        return str_starts_with($label, 'messages.') ? __($label) : $label;
    }
}
