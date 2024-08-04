<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Factory;
use NotificationChannels\OneSignal\OneSignalButton;
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
        $type = $data['type'];
        $content = $data['content'];

        if ($type === 'request') {
            $body = 'You have a new swap request.';
            $buttons = [
                OneSignalButton::create('decline-button')->text('Decline'),
                OneSignalButton::create('approve-button')->text('Approve'),
            ];
        } else {
            $body = $content;
            $buttons = [];
        }
        $response = OneSignalMessage::create()
            ->setSubject($data['sender_name'])
            ->setBody($body)
            ->setButtons($buttons)
            ->setData('data', $data);
        return $response;
    }
}
