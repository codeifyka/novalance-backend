<?php

namespace App\Notifications;

use App\Models\Purchase;
use App\Models\Service;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ServicePurchasedNotification extends Notification
{
    use Queueable;

    public Purchase $purchase;
    public Service $service;
    public User $client;

    /**
     * Create a new notification instance.
     */
    public function __construct(Purchase $purchase, Service $service, User $client)
    {
        $this->purchase = $purchase;
        $this->service = $service;
        $this->client = $client;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $clientName = trim("{$this->client->first_name} {$this->client->last_name}") ?: $this->client->username;

        return [
            'type' => 'service_purchased',
            'title' => 'Service Purchased!',
            'message' => "{$clientName} purchased your service '{$this->service->title}'.",
            'link' => '/my_purchases',
            'service_id' => $this->service->id,
            'purchase_id' => $this->purchase->id,
            'price' => $this->purchase->price,
            'sender_id' => $this->client->id,
            'sender_name' => $clientName,
            'sender_avatar' => $this->client->profile_picture,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'service_purchased';
    }
}
