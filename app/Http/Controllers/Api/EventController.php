<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Requests\StoreEventRequest;
use App\Http\Controllers\Api\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Mail\EventInvitationMail;
use App\Http\Controllers\Controller;

class EventController extends Controller
{
    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();

        $event = Event::create($validated);

        Mail::to($validated['attendee_email'])->send(new EventInvitationMail($event, $validated['attendee_email']));

        return response()->json($event, 201);
    }

    public function index() {
        return Event::all()->map(fn($event) => [
            'id' => $event->id,
            'title' => $event->title,
            'start' => $event->start_time,
            'end' => $event->end_time,
        ]);
    }

    public function show(Event $event)
    {
        return $event;
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $validated = $request->validated();

        $event->update($validated);

        return response()->json($event);
    }

    public function getUserEvents($userId, Request $request)
    {
        $startDate = $request->query('start'); 
        $endDate = $request->query('end');     

        return Event::where('user_id', $userId)
                    ->whereBetween('start_time', [$startDate, $endDate])
                    ->get();
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(null, 204);
    }
}
