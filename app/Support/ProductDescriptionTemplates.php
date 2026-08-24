<?php

namespace App\Support;

use App\Models\ProductDescriptionTemplate;
use Illuminate\Support\Facades\Schema;

class ProductDescriptionTemplates
{
    public const DEFAULT_NAME = 'Concise product overview';

    public const DEFAULT_INSTRUCTION = 'Write only a concise product overview in one or two paragraphs. Do not generate any additional sections.';

    private const LEGACY_DEFAULT_NAME = 'Comprehensive editorial description';

    private const LEGACY_DEFAULT_INSTRUCTION = 'Generate a comprehensive, detailed editorial description using the full structure and style specified above.';

    public function activeInstruction(): ?string
    {
        if (! Schema::hasTable('product_description_templates')) {
            return null;
        }

        $template = ProductDescriptionTemplate::query()
            ->where('is_active', true)
            ->latest('updated_at')
            ->first(['name', 'instruction']);

        if (! $template) {
            return null;
        }

        if (($template->name === self::DEFAULT_NAME && $template->instruction === self::DEFAULT_INSTRUCTION)
            || ($template->name === self::LEGACY_DEFAULT_NAME && $template->instruction === self::LEGACY_DEFAULT_INSTRUCTION)) {
            return null;
        }

        return $template->instruction;
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
