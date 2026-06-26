<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $attendeeEmail
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->attendeeEmail,
            subject: 'Invitation: ' . $this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-invitation',
        );
    }

    public function attachments(): array
    {
        $utcStart = gmdate('Ymd\THis\Z', $this->event->start_time->getTimestamp());
        $utcEnd = gmdate('Ymd\THis\Z', time());
        $utcNow = gmdate('Ymd\THis\Z');

        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MyCalendar//EN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:' . md5($this->event->id) . '@' . request()->getHost(),
            'DTSTAMP:' . $utcNow,
            'DTSTART:' . $utcStart,
            'DTEND:' . $utcEnd,
            'SUMMARY:' . $this->event->title,
            'DESCRIPTION:' . $this->event->description,
            'END:VEVENT',
            'END:VCALENDAR'
        ];

        $icsContent = implode("\r\n", $ics);

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $icsContent, 'invite.ics')
                ->withMime('text/calendar; method=REQUEST'),
        ];
    }
}