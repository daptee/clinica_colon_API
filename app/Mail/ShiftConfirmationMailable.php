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

class ShiftConfirmationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $date, $time, $clinic_name, $shift;
    /**
     * Create a new message instance.
     */
    public function __construct($shift)
    {
        $this->date = Carbon::parse($shift->date)->format('d/m/Y');
        $this->time = $shift->time;
        $this->clinic_name = Helper::getClinicName(Auth::user()->id_user_type, Auth::user()->id);
        $this->shift = $shift;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmacion de turno - $this->date $this->time - $this->clinic_name",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.shift_confirmation',
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
