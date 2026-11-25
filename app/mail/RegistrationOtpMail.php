<?php

namespace App\Mail;

use App\Models\AppSetting;
use Illuminate\Mail\Mailable;

class RegistrationOtpMail extends Mailable
{
    public $name;
    public $code;
    public $minutes;
    public $appName;
    public $brandColor;

    public function __construct(string $name, string $code, int $minutes)
    {
        $settings = AppSetting::current();

        $this->name       = $name;
        $this->code       = $code;
        $this->minutes    = $minutes;
        $this->appName    = $settings->application_name ?: 'BudgetWise';
        $this->brandColor = $settings->primary_color ?: '#4f39fa';
    }

    public function build()
    {
        return $this
            ->subject("{$this->code} is your {$this->appName} verification code")
            ->view('emails.registration-otp');
    }
}