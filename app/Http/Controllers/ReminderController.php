<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function index(Request $request): View
    {
        $reminders = Reminder::where('user_id', $request->user()->id)
            ->orderByRaw('completed_at IS NOT NULL')
            ->orderBy('remind_on')
            ->paginate(15);

        return view('reminders.index', compact('reminders'));
    }

    public function complete(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('complete', $reminder);

        $reminder->completed_at = now();
        $reminder->save();

        return redirect()
            ->route('reminders.index')
            ->with('status', __('Reminder marked as done.'));
    }
}