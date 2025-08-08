<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Helpers\HtmlHelper;

class AddNofollowToProductDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:add-nofollow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add rel="ugc nofollow" to all links in product descriptions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $product->description = HtmlHelper::addNofollowToLinks($product->description);
            $product->save();
        }

        $this->info('All product descriptions have been updated.');

        return 0;
    }
}