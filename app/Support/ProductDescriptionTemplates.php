<?php

namespace App\Support;

use App\Models\ProductDescriptionTemplate;
use Illuminate\Support\Facades\Schema;

class ProductDescriptionTemplates
{
    public const DEFAULT_NAME = 'Comprehensive editorial description';

    public const DEFAULT_INSTRUCTION = 'Generate a comprehensive, detailed editorial description using the full structure and style specified above.';

    public function activeInstruction(): ?string
    {
        if (! Schema::hasTable('product_description_templates')) {
            return null;
        }

        return ProductDescriptionTemplate::query()
            ->where('is_active', true)
            ->latest('updated_at')
            ->value('instruction');
    }

    public function ensureDefaultExists(): void
    {
        if (ProductDescriptionTemplate::query()->exists()) {
            return;
        }

        ProductDescriptionTemplate::query()->create([
            'name' => self::DEFAULT_NAME,
            'instruction' => self::DEFAULT_INSTRUCTION,
            'is_active' => true,
        ]);
    }
}
