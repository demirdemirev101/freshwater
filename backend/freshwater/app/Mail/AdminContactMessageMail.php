<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function build()
    {
        return $this->subject('New Contact Message Received')
                    ->view('emails.admin_contact_message')
                    ->with([
                        'name' => $this->contact->name,
                        'email' => $this->contact->email,
                        'phone' => $this->contact->phone ?? 'N/A',
                        'messageContent' => $this->contact->message,
                    ]);
    }
}
