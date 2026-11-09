<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Order '.$this->order->order_number.': '.$this->order->status_label)
            ->greeting('Hello '.$this->order->name.',')
            ->line('Your order '.$this->order->order_number.' is now: '.$this->order->status_label.'.');

        if ($this->order->courier_name || $this->order->tracking_number) {
            $mail->line('Courier: '.($this->order->courier_name ?: '-').' | Tracking no.: '.($this->order->tracking_number ?: '-'));
        }

        return $mail->action('Track your order', route('track-order'));
    }
}
