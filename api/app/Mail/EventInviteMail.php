<?php

namespace App\Mail;

use App\Models\Event\Invite;
use App\Models\Student\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $invite;
    public $student;

    /**
     * Create a new message instance.
     */
    public function __construct(Invite $invite, Student $student)
    {
        $this->invite = $invite;
        $this->student = $student;
    }

    /**
     * Get the message envelope (Assunto do Email).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invite->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Podes criar uma view blade bonita em resources/views/emails/event_invite.blade.php
        return new Content(
            view: 'emails.event_invite',
            // Passamos as variáveis para a view blade
            with: [
                'studentName'  => $this->student->name,
                'messageBody'  => $this->invite->message,
                'eventTitle'   => $this->invite->event->title ?? 'Evento',
                'eventDate'    => $this->invite->event->start_time ?? '',
                // Poderias passar aqui também um token/link para o aluno clicar em "Aceitar"
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}