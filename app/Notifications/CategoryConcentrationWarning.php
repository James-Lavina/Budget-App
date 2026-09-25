<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CategoryConcentrationWarning extends Notification
{
    use Queueable;

    public $categoryName;
    public $percentage;
    public $categoryTotal;
    public $totalSpent;

    public function __construct($categoryName, $percentage, $categoryTotal, $totalSpent = null)
    {
        $this->categoryName  = $categoryName;
        $this->percentage    = $percentage;
        $this->categoryTotal = $categoryTotal;
        $this->totalSpent    = $totalSpent;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $of = $this->totalSpent !== null ? ' of ₱' . number_format($this->totalSpent, 2) . ' spent' : '';

        return [
            'anomaly_type'  => 'category_concentration',
            'severity_tier' => 'medium',
            'category'      => $this->categoryName,
            'description'   => "Category Watch 👀: {$this->percentage}% of what you've spent this week (₱"
                . number_format($this->categoryTotal, 2) . "{$of}) went to {$this->categoryName}. Check if there's room to trim it.",
            'resolved'      => false,
        ];
    }
}