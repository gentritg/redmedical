<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'status',
        'external_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TYPE_CONNECTOR = 'connector';
    public const TYPE_VPN_CONNECTION = 'vpn_connection';

    public const STATUS_ORDERED = 'ordered';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    public static function getTypes(): array
    {
        return [
            self::TYPE_CONNECTOR,
            self::TYPE_VPN_CONNECTION,
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ORDERED,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
        ];
    }
} 