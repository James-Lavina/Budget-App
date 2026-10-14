<?php

namespace App\Http\Livewire\Admin;

use App\Http\Livewire\Concerns\EnsuresAdmin;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class AdminManagement extends Component
{
    use WithPagination, EnsuresAdmin;

    protected $paginationTheme = 'tailwind';

    public $showCreateModal = false;
    public $create_name;
    public $create_email;
    public $create_password;
    public $create_password_confirmation;

    public $confirmingDemoteId = null;
    public $confirmingDeleteId = null;

    protected $rules = [
        'create_name'     => 'required|string|max:255',
        'create_email'    => 'required|email|max:255|unique:users,email',
        'create_password' => 'required|string|min:8|confirmed',
    ];

    protected $messages = [
        'create_password.confirmed' => 'Password confirmation does not match.',
    ];

    protected function allowedRoles(): array
    {
        return ['super_admin'];
    }

    public function openCreate()
    {
        $this->reset(['create_name', 'create_email', 'create_password', 'create_password_confirmation']);
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeCreate()
    {
        $this->showCreateModal = false;
    }

    public function store()
    {
        $this->validate();

        try {
            $newAdmin = User::create([
                'name'     => $this->create_name,
                'email'    => $this->create_email,
                'password' => Hash::make($this->create_password),
                'role'     => 'admin',
                'status'   => 'active',
            ]);

            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'admin_created',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => "Created new admin account: {$newAdmin->name} ({$newAdmin->email})",
            ]);
        } catch (\Exception $e) {
            \Log::error('AdminManagement::store() failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to create admin: ' . $e->getMessage());
            return;
        }

        $this->showCreateModal = false;
        session()->flash('success', "\"{$newAdmin->name}\" was granted admin access.");
    }

    public function confirmDemote($id)
    {
        $this->confirmingDemoteId = $id;
    }

    public function cancelDemote()
    {
        $this->confirmingDemoteId = null;
    }

    public function demote()
    {
        if (!$this->confirmingDemoteId) {
            return;
        }

        $admin = User::where('role', 'admin')->findOrFail($this->confirmingDemoteId);

        $admin->update(['role' => 'student']);

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'admin_demoted',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Revoked admin access from: {$admin->name} ({$admin->email})",
        ]);

        $this->confirmingDemoteId = null;
        session()->flash('success', "{$admin->name}'s admin access was revoked.");
    }

    public function confirmDelete($id)
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete()
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteAdmin()
    {
        if (!$this->confirmingDeleteId) {
            return;
        }

        $admin   = User::where('role', 'admin')->findOrFail($this->confirmingDeleteId);
        $name    = $admin->name;
        $email   = $admin->email;
        $adminId = $admin->id;

        DB::transaction(function () use ($admin, $name, $email, $adminId) {
            // activity_logs.user_id cascades on delete, which would erase this admin's audit trail.
            // Detach the rows first and keep the admin's identity in the details text.
            DB::update(
                "UPDATE activity_logs SET user_id = NULL, details = CONCAT(COALESCE(details, ''), ?) WHERE user_id = ?",
                [" [by former admin: {$name}, ID {$adminId}]", $adminId]
            );

            $admin->notifications()->delete();
            $admin->tokens()->delete();
            DB::table('password_resets')->where('email', $email)->delete();

            $admin->delete();
        });

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'admin_deleted',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Permanently deleted admin account: {$name} ({$email})",
        ]);

        $this->confirmingDeleteId = null;

        session()->flash('success', "{$name}'s admin account was permanently deleted.");
    }

    public function render()
    {
        $admins = User::where('role', 'admin')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.admin-management', [
            'admins' => $admins,
        ])->layout('layouts.admin');
    }
}