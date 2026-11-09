<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification implements ShouldQueue
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
            ->subject('Order '.$this->order->order_number.' received - '.setting('business_name', 'Arsh Perfume'))
            ->greeting('Thank you, '.$this->order->name.'!')
            ->line('We have received your order '.$this->order->order_number.'.');

        foreach ($this->order->items as $item) {
            $mail->line($item->product_name.($item->variant_name ? ' ('.$item->variant_name.')' : '').' x '.$item->quantity.' - '.money($item->line_total));
        }

        $mail->line('Total: '.money($this->order->grand_total).' ('.$this->order->payment_method_label.')');

        if ($this->order->payment_method === 'razorpay' && ! $this->order->isPaid()) {
            $mail->line('Your order will be confirmed once the online payment is completed.');
        }
        if ($this->order->payment_method === 'upi' && ! $this->order->isPaid()) {
            $mail->line('Pay using the UPI QR displayed after checkout and submit your transaction reference. We verify receipt before confirming the order. If you already paid, do not pay again.');
        }

        return $mail
            ->action('Track your order', route('track-order'))
            ->line('Delivering to: '.$this->order->shippingAddressOneLine());
    }
}
