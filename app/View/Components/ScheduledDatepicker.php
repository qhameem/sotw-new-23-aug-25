<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Carbon;

class ScheduledDatepicker extends Component
{
    public $minDate;
    public $name;
    public $value;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name = 'published_at', $value = '')
    {
        $this->name = $name;
        $this->value = $value;

        $now = Carbon::now('UTC');
        $publishingTimeToday = Carbon::today('UTC')->setTime(7, 0, 0);

        if ($now->greaterThan($publishingTimeToday)) {
            $this->minDate = Carbon::tomorrow('UTC')->toDateString();
        } else {
            $this->minDate = Carbon::today('UTC')->toDateString();
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.scheduled-datepicker');
    }
}