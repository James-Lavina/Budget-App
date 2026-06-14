<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;

class SimulationWidget extends Component
{
    public $quickAmount;

    protected $rules = [
        'quickAmount' => 'required|numeric|min:1|max:50000',
    ];

    // Customized error validation messages for a more supportive tone
    protected $messages = [
        'quickAmount.required' => 'Type in an amount first to test it out!',
        'quickAmount.numeric'  => 'Please enter numbers only.',
        'quickAmount.min'      => 'Enter an amount greater than zero.',
        'quickAmount.max'      => 'Whoa, that amount is a bit too high for a quick test!',
    ];

    public function calculateImpact()
    {
        $this->validate();

        // Redirect directly to the full simulation route with parameters
        return redirect()->route('student.simulation', [
            'scenarioType' => 'major_purchase',
            'purchaseAmount' => $this->quickAmount
        ]);
    }

    public function render()
    {
        return view('livewire.student.simulation-widget');
    }
}