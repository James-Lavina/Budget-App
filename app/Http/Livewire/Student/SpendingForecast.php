<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use App\Services\SpendingForecastService;
use App\Services\RiskDetectionService;

class SpendingForecast extends Component
{
    public $forecastResult;
    public $aiInsight = null;
    public $aiLoaded = false;

    public function mount()
    {
        // Single source of truth for risk-log creation/notifications —
        // SpendingForecastService no longer duplicates this.
        app(RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

        $service = app(SpendingForecastService::class);
        $this->forecastResult = $service->computeLocalForecast(auth()->user());
    }

    /**
     * Triggered via wire:init in the view — runs right after initial paint
     * so the cards/chart render instantly, and AI tips fill in a beat later
     * instead of blocking the whole page behind a 5s HTTP timeout.
     */
    public function loadAiInsight()
    {
        $service = app(SpendingForecastService::class);
        $this->aiInsight = $service->fetchAiInsight(auth()->user(), $this->forecastResult);
        $this->aiLoaded = true;
    }

    public function render()
    {
        return view('livewire.student.spending-forecast')->layout('layouts.student');
    }
}