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
    public $sidebarSticky;
    public $containerMaxWidth;
    public $lockHeight;
    public $headerPadding;
    public $mainPadding;
    public $hideSidebar;

    public function __construct(
        $mainContentMaxWidth = 'max-w-3xl',
        $sidebarSticky = true,
        $containerMaxWidth = 'max-w-7xl',
        $lockHeight = false,
        $headerPadding = 'px-4 sm:px-6 lg:px-4',
        $mainPadding = 'px-4 sm:px-6 lg:px-8',
        $hideSidebar = false
    ) {
        $this->mainContentMaxWidth = $mainContentMaxWidth;
        $this->sidebarSticky = $sidebarSticky;
        $this->containerMaxWidth = $containerMaxWidth;
        $this->lockHeight = $lockHeight;
        $this->headerPadding = $headerPadding;
        $this->mainPadding = $mainPadding;
        $this->hideSidebar = $hideSidebar;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.main-content-layout');
    }
}
