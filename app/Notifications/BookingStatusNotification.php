<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BookingStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;
    public $oldStatus;
    public $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $oldStatus, string $newStatus)
    {
        $this->booking = $booking;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->booking->customer->full_name ?? 'Valued Customer';
        $bookingNo = $this->booking->booking_number;
        $date = $this->booking->booking_date ? $this->booking->booking_date->format('M d, Y') : '';

        return (new MailMessage)
            ->subject("Booking Status Update: #{$bookingNo}")
            ->greeting("Hello {$customerName},")
            ->line("The status of your booking #{$bookingNo} for {$date} has been updated from **{$this->oldStatus}** to **{$this->newStatus}**.")
            ->action('View Booking', route('bookings.show', $this->booking->id))
            ->line('Thank you for choosing Royal Marquee CMS!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => 'Booking Status Updated',
            'message' => "Your booking #{$this->booking->booking_number} has been updated from {$this->oldStatus} to {$this->newStatus}.",
        ];
    }
}
