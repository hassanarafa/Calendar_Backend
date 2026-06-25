<?php

namespace App\Http\Controllers;

// use Illuminate\Routing\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;

class EventController extends Controller
{
    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'attendee_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validated();

        $event = Event::create($validated);

        $this->sendEventInvitation($event, $validated['attendee_email']);

        return response()->json($event, 201);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Event::all();
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        return $event;
    }

    /**
     * Sends an email with an iCalendar attachment.
     */
    private function sendEventInvitation(Event $event, string $attendeeEmail)
    {
        $icsContent = $this->generateIcsContent($event);

        Mail::send([], [], function ($message) use ($attendeeEmail, $event, $icsContent) {
            $message->to($attendeeEmail)
                ->subject('Invitation: ' . $event->title);

            $attachment = new DataPart($icsContent, 'invite.ics', 'text/calendar; method=REQUEST');
            $attachment->asInline();

            $message->setBody(
                new AlternativePart(
                    $attachment
                )
            );
        });
    }

    /**
     * Generates iCalendar (.ics) file content for an event.
     */
    private function generateIcsContent(Event $event): string
    {
        $utcStart = gmdate('Ymd\THis\Z', $event->start_time->getTimestamp());
        $utcEnd = gmdate('Ymd\THis\Z', $event->end_time->getTimestamp());
        $utcNow = gmdate('Ymd\THis\Z');

        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MyCalendar//EN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:' . md5($event->id) . '@' . request()->getHost(),
            'DTSTAMP:' . $utcNow,
            'DTSTART:' . $utcStart,
            'DTEND:' . $utcEnd,
            'SUMMARY:' . $event->title,
            'DESCRIPTION:' . $event->description,
            'END:VEVENT',
            'END:VCALENDAR'
        ];

        return implode("\r\n", $ics);
    }
}
