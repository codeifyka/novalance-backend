<?php

namespace App\Notifications;

use App\Models\JobPost;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewProposalNotification extends Notification
{
    use Queueable;

    public Proposal $proposal;
    public JobPost $jobPost;
    public User $freelancer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Proposal $proposal, JobPost $jobPost, User $freelancer)
    {
        $this->proposal = $proposal;
        $this->jobPost = $jobPost;
        $this->freelancer = $freelancer;
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
        $freelancerName = trim("{$this->freelancer->first_name} {$this->freelancer->last_name}") ?: $this->freelancer->username;

        return [
            'type' => 'new_proposal',
            'title' => 'New Proposal Received',
            'message' => "{$freelancerName} submitted a proposal for '{$this->jobPost->title}'.",
            'link' => '/proposals',
            'job_post_id' => $this->jobPost->id,
            'proposal_id' => $this->proposal->id,
            'bid' => $this->proposal->bid,
            'sender_id' => $this->freelancer->id,
            'sender_name' => $freelancerName,
            'sender_avatar' => $this->freelancer->profile_picture,
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
        return 'new_proposal';
    }
}
