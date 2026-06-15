<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'link',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Send a notification to a specific user.
     */
    public static function send($userId, $title, $message, $type, $link = null)
    {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
        ]);
    }

    /**
     * Send a notification to all administrators.
     */
    public static function notifyAdmins($title, $message, $type, $link = null)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            self::send($admin->id, $title, $message, $type, $link);

            // Send email notification to admin
            try {
                $url = $link ? url($link) : null;
                $adminName = $admin->full_name ?: $admin->name;
                \Illuminate\Support\Facades\Mail::to($admin->email)->send(
                    new \App\Mail\CateringNotificationMail(
                        $title,
                        "Hi, {$adminName}!",
                        [$message],
                        $link ? 'View Details' : null,
                        $url
                    )
                );
            } catch (\Exception $e) {
                // Log the mail failure so it doesn't break the application runtime
                \Illuminate\Support\Facades\Log::error("Failed to send admin notification email to {$admin->email}: " . $e->getMessage());
            }
        }
    }
}
