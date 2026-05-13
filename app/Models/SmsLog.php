<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'type', 'sender_id', 'recipients', 'message',
        'total_count', 'success_count', 'failed_count',
        'api_response', 'status', 'campaign_name', 'sent_by',
    ];

    protected $casts = [
        'recipients'   => 'array',
        'api_response' => 'array',
    ];

    const TYPES = [
        'promotion' => 'Promotion',
        'birthday'  => 'Birthday Wish',
        'custom'    => 'Custom',
    ];

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
