<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Local-only testing helper. Re-applies a session-stored fake "now" for
 * the duration of the route handler + view rendering, then RESETS Carbon
 * back to real time before the response bubbles further up the middleware
 * stack.
 *
 * Why the reset matters: Laravel computes the session cookie's Expires
 * timestamp (and the "last_activity" value for the database session
 * driver) using Carbon::now(), and it does so AFTER the route handler
 * returns - in StartSession's "after" phase, which runs further out in
 * the middleware onion than this middleware. If we leave Carbon faked
 * past that point, jumping the fake date BACKWARD makes the cookie's
 * computed expiry earlier than the browser's real clock, so the browser
 * immediately discards the session cookie - logging the user out on the
 * very next request. Jumping FORWARD doesn't show this bug because the
 * computed expiry is still comfortably after real "now".
 *
 * Resetting here keeps the fake date scoped to "what the page renders",
 * while all session/cookie/auth infrastructure keeps using real time.
 */
class ApplyTestFakeNow
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('local') && session()->has('test_fake_now')) {
            Carbon::setTestNow(session('test_fake_now'));
        }

        $response = $next($request);

        // Restore real time before this bubbles up to StartSession's
        // cookie-expiration / session-save logic.
        Carbon::setTestNow(null);

        return $response;
    }
}