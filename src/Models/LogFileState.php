<?php

namespace Willypelz\LogPlatform\Models;

use Illuminate\Database\Eloquent\Model;

class LogFileState extends Model
{
    protected $table = 'log_platform_file_states';

    protected $fillable = [
        'env',
        'channel',
        'path',
        'inode',
        'last_offset',
        'last_seen_at',
        'last_hash',
        'status',
    ];

    protected $casts = [
        'last_offset' => 'integer',
        'inode' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get or create file state.
     */
    public static function getOrCreate(string $path, string $env, string $channel): self
    {
        return static::firstOrCreate(
            ['path' => $path, 'env' => $env, 'channel' => $channel],
            ['status' => 'active', 'last_offset' => 0]
        );
    }

    /**
     * Mark as rotated.
     */
    public function markRotated(): void
    {
        $this->update(['status' => 'rotated']);
    }

    /**
     * Mark as missing.
     */
    public function markMissing(): void
    {
        $this->update(['status' => 'missing']);
    }
}

