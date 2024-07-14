<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Factory;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class MessageNotification extends Notification
{
    use Queueable;

    protected $messaging;

    /**
     * Create a new notification instance.
     */
    public function __construct(private array $data)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return [
            OneSignalChannel::class,
        ];
    }

    /**
     * Get the OneSignal representation of the notification.
     *
     * @return OneSignalMessage
     */
    public function toOneSignal(): OneSignalMessage
    {
        $data = $this->data['message_data'];
        return OneSignalMessage::create()
            ->setSubject($data['sender_name'] . ' sent you a message.')
            ->setBody($data['message'])
            ->setData('data', $data);
    }
}
