<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public $name;
    public $email;
    public $school;
    public $email_notifications_enabled = true;

    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public $confirmingAccountDeletion = false;
    public $delete_password;

    public function mount()
    {
        $user = auth()->user();

        $this->name   = $user->name;
        $this->email  = $user->email;
        $this->school = $user->school;
        $this->email_notifications_enabled = (bool) $user->email_notifications_enabled;
    }

    public function updateProfile()
    {
        $user = auth()->user();

        $isChangingEmail    = ($this->email !== $user->email);
        $isChangingPassword = !empty($this->new_password);

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'school' => 'nullable|string|max:255',
            'email_notifications_enabled' => 'boolean',
            'current_password' => [
                ($isChangingEmail || $isChangingPassword) ? 'required' : 'nullable',
                'string'
            ],
            'new_password' => 'nullable|min:8|confirmed',
        ], [
            'current_password.required' => 'You must enter your current password to authorize changes to your security profile.',
        ]);

        if ($isChangingEmail || $isChangingPassword) {
            if (!Hash::check($this->current_password, $user->password)) {
                $this->addError('current_password', 'The provided current password does not match your records.');
                return;
            }
        }

        $changes = [];

        if ($user->name !== $this->name) {
            $changes[] = "name: \"{$user->name}\" → \"{$this->name}\"";
        }

        if ($isChangingEmail) {
            $changes[] = "email: \"{$user->email}\" → \"{$this->email}\"";
        }

        if ($user->school !== $this->school) {
            $changes[] = "school: \"" . ($user->school ?? '—') . "\" → \"" . ($this->school ?? '—') . "\"";
        }

        $newEmailPref = (bool) $this->email_notifications_enabled;

        if ((bool) $user->email_notifications_enabled !== $newEmailPref) {
            $changes[] = 'email alerts: ' . ($user->email_notifications_enabled ? 'On' : 'Off') . ' → ' . ($newEmailPref ? 'On' : 'Off');
        }

        if ($isChangingPassword) {
            $user->password = Hash::make($this->new_password);
        }

        $user->name   = $this->name;
        $user->email  = $this->email;
        $user->school = $this->school;
        $user->email_notifications_enabled = $newEmailPref;
        $user->save();

        if ($isChangingPassword) {
            Auth::logoutOtherDevices($this->new_password);
        }

        if (!empty($changes)) {
            ActivityLog::create([
                'user_id'    => $user->id,
                'event_type' => 'profile_updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Updated profile: ' . implode(', ', $changes),
            ]);
        }

        if ($isChangingPassword) {
            ActivityLog::create([
                'user_id'    => $user->id,
                'event_type' => 'password_changed',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Account password was changed. Other sessions were signed out.',
            ]);
        }

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('success', 'Your profile details have been successfully secured and updated.');
    }

    public function exportData()
    {
        $user = auth()->user();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name'                        => $user->name,
                'email'                       => $user->email,
                'school'                      => $user->school,
                'default_allowance'           => (float) $user->default_allowance,
                'default_reset_day'           => $user->default_reset_day,
                'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
                'registered_at'               => optional($user->created_at)->toIso8601String(),
            ],
            'weekly_budgets' => $user->weeklyBudgets()
                ->get(['total_allowance', 'remaining_allowance', 'reset_day', 'cycle_start_date', 'created_at'])
                ->toArray(),
            'expenses' => $user->expenses()->with('category:id,name')->get()->map(function ($e) {
                return [
                    'date'            => optional($e->transaction_date)->toIso8601String(),
                    'item'            => $e->item_name,
                    'category'        => $e->category->name ?? null,
                    'amount'          => (float) $e->amount,
                    'tracking_type'   => $e->tracking_type,
                    'savings_goal_id' => $e->savings_goal_id,
                ];
            })->all(),
            'savings_goals' => $user->savingsGoals()
                ->get(['id', 'target_name', 'target_amount', 'current_saved', 'target_date', 'status', 'created_at'])
                ->toArray(),
            'receipts' => $user->receipts()
                ->get(['expense_id', 'status', 'created_at'])
                ->toArray(),
            'risk_alerts' => $user->riskLogs()
                ->get(['anomaly_type', 'severity_tier', 'description', 'resolved', 'created_at'])
                ->toArray(),
            'simulations' => $user->whatIfSimulations()
                ->get(['item_title', 'amount', 'simulated_at'])
                ->toArray(),
            'notifications' => $user->notifications()->get()->map(function ($n) {
                return [
                    'data'       => $n->data,
                    'read_at'    => optional($n->read_at)->toIso8601String(),
                    'created_at' => optional($n->created_at)->toIso8601String(),
                ];
            })->all(),
            'activity_log' => $user->activityLogs()
                ->get(['event_type', 'details', 'ip_address', 'created_at'])
                ->toArray(),
        ];

        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'data_exported',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => 'Downloaded a copy of personal data.',
        ]);

        $filename = Str::slug(AppSetting::current()->application_name ?? 'budgetwise')
            . '-my-data-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function openDeleteModal()
    {
        $this->reset('delete_password');
        $this->resetErrorBag('delete_password');
        $this->confirmingAccountDeletion = true;
    }

    public function closeDeleteModal()
    {
        $this->confirmingAccountDeletion = false;
        $this->reset('delete_password');
    }

    public function deleteAccount()
    {
        $this->validate(
            ['delete_password' => 'required|string'],
            ['delete_password.required' => 'Enter your password to confirm.']
        );

        $user = auth()->user();

        if (!Hash::check($this->delete_password, $user->password)) {
            $this->addError('delete_password', 'The provided password does not match your records.');
            return;
        }

        $userId       = $user->id;
        $email        = $user->email;
        $receiptPaths = $user->receipts()->pluck('image_path')->filter()->values()->all();

        // Log out FIRST. logout() cycles the remember token and saves the model;
        // doing it after delete() would re-insert the deleted user.
        auth()->logout();

        DB::transaction(function () use ($user, $email) {
            // No FK on these, so they are not removed by the user cascade.
            $user->notifications()->delete();
            $user->tokens()->delete();
            DB::table('password_resets')->where('email', $email)->delete();

            // Cascades: weekly_budgets, expenses, receipts, savings_goals,
            // what_if_simulations, risk_logs, activity_logs.
            $user->delete();
        });

        if (!empty($receiptPaths)) {
            Storage::disk('local')->delete($receiptPaths);
            Storage::disk('public')->delete($receiptPaths);
        }

        // Orphaned on purpose (user_id is nullable); stores the ID only, no personal data.
        ActivityLog::create([
            'user_id'    => null,
            'event_type' => 'account_deleted',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Student account deleted by its owner (former ID {$userId}).",
        ]);

        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.student.profile')->layout('layouts.student');
    }
}