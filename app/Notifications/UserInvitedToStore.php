<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserInvitedToStore extends Notification
{
    use Queueable;

    public function __construct(private array $data)
    {
    }

    public function via(object $notifiable): array
    {
        // Notifikasi in-app (tabel notifications). Email tidak dikirim karena
        // mailer di project ini hanya "log".
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->data;
    }

    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}