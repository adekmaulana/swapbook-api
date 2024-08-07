<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\MessageNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are guarded.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'tokens',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Is the user an administrator?
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'created_by');
    }

    public function location(): HasOne
    {
        return $this->hasOne(Location::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id')->latest();
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'user_id');
    }

    public function bookmark(Post $post)
    {
        if ($this->isBookmarked($post)) {
            // delete bookmark/unbookmark
            return $this->bookmarks()->where(
                [
                    ['post_id' => $post->id],
                    ['user_id' => $this->id],
                ]
            )->delete();
        }

        $this->bookmarks()->create(
            [
                'post_id' => $post->id,
                'user_id' => $this->id,
            ]
        );
    }

    public function isBookmarked(Post $post): bool
    {
        return $this->bookmarks()
            ->newQuery()
            ->where(
                [
                    ['post_id', $post->id],
                    ['user_id', $this->id],
                ]
            )
            ->exists();
    }

    public function routeNotificationForOneSignal(): array
    {
        return [
            'tags' => [
                [
                    'key' => 'user_id', 'relation' => '=', 'value' => (string)($this->id)
                ],
            ],
        ];
    }

    public function sendMessageNotification(array $data): void
    {
        $this->notify(new MessageNotification($data));
    }
}
