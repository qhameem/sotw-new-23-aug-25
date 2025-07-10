<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MainContentLayout extends Component
{
    /**
     * Create a new component instance.
     */
    public $mainContentMaxWidth;

    public function __construct($mainContentMaxWidth = 'max-w-2xl')
    {
        $this->mainContentMaxWidth = $mainContentMaxWidth;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.main-content-layout');
    }
}
