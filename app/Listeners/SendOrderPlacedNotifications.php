<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderPlacedNotifications implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing('items');

        Notification::route('mail', $order->email)->notify(new OrderPlacedNotification($order));

        $adminEmail = setting('admin_notification_email') ?: setting('contact_email');
        if (setting('notify_admin_new_order', true) && filled($adminEmail)) {
            Notification::route('mail', $adminEmail)->notify(new NewOrderAdminNotification($order));
        }
    }
}
