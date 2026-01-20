<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\Permission\Models\Role;

class RoleAssignedNotification extends Notification
{
    use Queueable;

    public $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Role Assigned')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have been assigned a new role: ' . $this->role->name)
            ->line('This role grants you ' . $this->role->permissions->count() . ' permissions.')
            ->action('Login to Your Account', url('/login'))
            ->line('If you have any questions, please contact your administrator.');
    }
}