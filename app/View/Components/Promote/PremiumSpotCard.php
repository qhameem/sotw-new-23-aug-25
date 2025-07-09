<?php

namespace App\View\Components\Promote;

use Illuminate\View\Component;

class PremiumSpotCard extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $spotsAvailable;

    public function __construct($spotsAvailable)
    {
        $this->spotsAvailable = $spotsAvailable;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.promote.premium-spot-card');
    }
}