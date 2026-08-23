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

  public function __construct($categoryName, $percentage, $categoryTotal)
  {
      $this->categoryName = $categoryName;
      $this->percentage = $percentage;
      $this->categoryTotal = $categoryTotal;
  }

  public function via($notifiable)
  {
      return ['database'];
  }

  public function toArray($notifiable)
  {
      return [
          'anomaly_type'  => 'category_concentration',
          'severity_tier' => 'medium',
          'category'      => $this->categoryName,
          'description'   => "Category Watch 👀: {$this->percentage}% of this week's spending (₱" . number_format($this->categoryTotal, 2) . ") has gone to {$this->categoryName}.",
      ];
  }
}