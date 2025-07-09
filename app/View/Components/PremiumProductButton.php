<?php

namespace App\View\Components;

use App\Models\PremiumProduct;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;

class PremiumProductButton extends Component
{
    public bool $disabled;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $settings = [];
        if (Storage::disk('local')->exists('settings.json')) {
            $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        }
        $premiumProductSpots = $settings['premium_product_spots'] ?? 6;
        $this->disabled = PremiumProduct::where('expires_at', '>', now())->count() >= $premiumProductSpots;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.premium-product-button');
    }
}
