<?php

namespace App\Http\Middleware;

use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use Closure;

class EnsureCycleIsCurrent
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return $next($request);
        }

        $budget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        // No budget yet: the dashboard sends the student to the first-time setup wizard.
        if (!$budget || !app(BudgetCycleService::class)->needsRollover($budget, $user)) {
            return $next($request);
        }

        // Livewire AJAX: 419 makes the page reload, and the reload hits the redirect below.
        if ($request->header('X-Livewire')) {
            return response()->json(['message' => 'Cycle ended.'], 419);
        }

        return redirect()->route('student.new-cycle');
    }
}