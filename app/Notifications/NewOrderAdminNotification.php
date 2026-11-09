<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderAdminNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('New order '.$this->order->order_number.' - '.money($this->order->grand_total))
            ->line('A new order was placed by '.$this->order->name.'.')
            ->line('Payment: '.$this->order->payment_method_label.' ('.$this->order->payment_status_label.')')
            ->line('Total: '.money($this->order->grand_total))
            ->action('Open in admin', route('admin.orders.show', $this->order));
    }
}
