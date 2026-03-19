<?php

namespace Willypelz\LogPlatform\Models;

use Illuminate\Database\Eloquent\Model;

class IndexedLog extends Model
{
    protected $table = 'log_platform_indexed_logs';

    protected $fillable = [
        'env',
        'channel',
        'level',
        'logged_at',
        'message',
        'context',
        'request_id',
        'fingerprint',
        'source_file',
        'source_offset',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'context' => 'array',
        'source_offset' => 'integer',
    ];

    public $timestamps = false;

    /**
     * Scope by level.
     */
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope by environment.
     */
    public function scopeEnvironment($query, $env)
    {
        return $query->where('env', $env);
    }

    /**
     * Scope by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('logged_at', [$from, $to]);
    }

    /**
     * Scope by request ID.
     */
    public function scopeRequestId($query, $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    /**
     * Scope by fingerprint.
     */
    public function scopeFingerprint($query, $fingerprint)
    {
        return $query->where('fingerprint', $fingerprint);
    }

    /**
     * Scope by keyword search.
     */
    public function scopeKeyword($query, $keyword)
    {
        return $query->where('message', 'like', '%' . $keyword . '%');
    }
}

