<?php

namespace App\Http\Livewire\Admin;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ActivityLog;

class Settings extends Component
{
    use WithFileUploads;

    public const COLOR_PRESETS = [
        'indigo'  => ['label' => 'Indigo',  'value' => '#4f39fa'],
        'blue'    => ['label' => 'Blue',    'value' => '#2563eb'],
        'emerald' => ['label' => 'Emerald', 'value' => '#059669'],
        'rose'    => ['label' => 'Rose',    'value' => '#e11d48'],
        'amber'   => ['label' => 'Amber',   'value' => '#d97706'],
        'purple'  => ['label' => 'Purple',  'value' => '#7c3aed'],
    ];

    public $application_name;
    public $primary_color;
    public $logo;
    public $existing_logo_path;

    public $email_notifications_enabled;
    public $maintenance_mode_enabled;

    protected $rules = [
        'application_name' => 'required|string|max:255',
        'primary_color'    => 'required|string|max:7|in:' . '', // set dynamically below
        'logo'             => 'nullable|image|max:2048',
    ];

    protected function rules()
    {
        return [
            'application_name' => 'required|string|max:255',
            'primary_color'    => 'required|string|in:' . implode(',', array_column(self::COLOR_PRESETS, 'value')),
            'logo'             => 'nullable|image|max:2048',
        ];
    }

    public function mount()
    {
        $s = AppSetting::current();

        $this->application_name            = $s->application_name;
        $this->primary_color               = $s->primary_color;
        $this->existing_logo_path          = $s->logo_path;
        $this->email_notifications_enabled = $s->email_notifications_enabled;
        $this->maintenance_mode_enabled    = $s->maintenance_mode_enabled;
    }

    public function removeLogo()
    {
        $s = AppSetting::current();
        if ($s->logo_path) {
            Storage::disk('public')->delete($s->logo_path);
            $s->update(['logo_path' => null]);
            AppSetting::flush();

            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'app_settings_updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Removed application logo',
            ]);
        }

        $this->logo = null;
        $this->existing_logo_path = null;
        session()->flash('success', 'Logo removed.');
    }

    public function save()
    {
        $this->validate();

        $s = AppSetting::current();

        $changes = [];
        if ($s->application_name !== $this->application_name) {
            $changes[] = "Application Name: \"{$s->application_name}\" → \"{$this->application_name}\"";
        }
        if ($s->primary_color !== $this->primary_color) {
            $changes[] = "Primary Color: {$s->primary_color} → {$this->primary_color}";
        }
        if ((bool) $s->email_notifications_enabled !== (bool) $this->email_notifications_enabled) {
            $changes[] = 'Email Notifications: ' . ($s->email_notifications_enabled ? 'On' : 'Off') . ' → ' . ($this->email_notifications_enabled ? 'On' : 'Off');
        }

        $maintenanceWasEnabled = (bool) $s->maintenance_mode_enabled;
        $maintenanceWillBeEnabled = (bool) $this->maintenance_mode_enabled;
        $maintenanceChanged = $maintenanceWasEnabled !== $maintenanceWillBeEnabled;
        if ($maintenanceChanged) {
            $changes[] = 'Maintenance Mode: ' . ($maintenanceWasEnabled ? 'On' : 'Off') . ' → ' . ($maintenanceWillBeEnabled ? 'On' : 'Off');
        }

        $data = [
            'application_name'            => $this->application_name,
            'primary_color'               => $this->primary_color,
            'email_notifications_enabled' => $this->email_notifications_enabled,
            'maintenance_mode_enabled'    => $this->maintenance_mode_enabled,
        ];

        $logoChanged = false;
        if ($this->logo) {
            if ($s->logo_path) {
                Storage::disk('public')->delete($s->logo_path);
            }
            $data['logo_path'] = $this->logo->store('branding', 'public');
            $logoChanged = true;
            $changes[] = 'Application logo replaced';
        }

        $s->update($data);
        AppSetting::flush();

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => $maintenanceChanged
                ? ($maintenanceWillBeEnabled ? 'maintenance_mode_enabled' : 'maintenance_mode_disabled')
                : 'app_settings_updated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => !empty($changes)
                ? 'Updated application settings: ' . implode(', ', $changes)
                : 'Saved application settings — no field changes detected',
        ]);

        $this->logo = null;
        $this->existing_logo_path = $s->fresh()->logo_path;
        session()->flash('success', 'Settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout('layouts.admin');
    }
}