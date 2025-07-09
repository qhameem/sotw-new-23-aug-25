<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\UserProductUpvote;
use Illuminate\Support\Facades\Auth;

class ProductUpvoteButton extends Component
{
    public Product $product;
    public int $votesCount;
    public bool $hasUpvoted;

    public function mount(Product $product)
    {
        $this->product = $product;
        $this->votesCount = $product->votes_count;
        $this->hasUpvoted = $product->is_upvoted_by_current_user;
    }

    public function toggleUpvote()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($this->hasUpvoted) {
            // Remove upvote
            $this->product->userUpvotes()->where('user_id', $user->id)->delete();
            $this->votesCount--;
            $this->hasUpvoted = false;
        } else {
            // Add upvote
            $this->product->userUpvotes()->create(['user_id' => $user->id]);
            $this->votesCount++;
            $this->hasUpvoted = true;
        }

        $this->product->update(['votes_count' => $this->votesCount]);
    }

    public function render()
    {
        return view('livewire.product-upvote-button');
    }
}