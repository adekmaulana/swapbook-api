<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function participants()
    {
        return $this->hasMany(ChatParticipant::class, 'chat_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'chat_id')->latest('created_at');
    }

    public function scopeHasParticipant($query, $userId)
    {
        return $query->whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    public function scopeGetUnreadCount($query)
    {
        return $query->withCount([
            'messages as unread_count' => function ($query) {
                $query->where('user_id', '!=', auth('sanctum')->user()->id)
                    ->where('is_read', 0);
            },
        ]);
    }
}
