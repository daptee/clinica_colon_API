<?php

namespace App\Mail;

use App\Models\Helper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class ShiftCancellationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $date, $professional_name, $clinic_name, $shift, $text;
    /**
     * Create a new message instance.
     */
    public function __construct($shift, $text)
    {
        $this->professional_name = $shift->professional->name;
        $this->clinic_name = Helper::getClinicName(Auth::user()->id_user_type, Auth::user()->id);
        $this->shift = $shift;
        $this->text = $text;
        $this->date = Carbon::parse($shift->date)->format('d/m/Y');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cancelación de turno - $this->professional_name - $this->clinic_name",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.shift_cancellation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
