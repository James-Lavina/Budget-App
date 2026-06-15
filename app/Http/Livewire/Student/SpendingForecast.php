<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use App\Services\SpendingForecastService;

class SpendingForecast extends Component
{
    public $forecastResult;

    /**
     * Component mounting handler hook.
     */
    public function mount()
    {
        $service = app(SpendingForecastService::class);
        $this->forecastResult = $service->generateForecast(auth()->user());
    }

    /**
     * Renders the frontend layout context using our custom student layout wrapper.
     */
    public function render()
    {
        return view('livewire.student.spending-forecast')->layout('layouts.student');
    }
}