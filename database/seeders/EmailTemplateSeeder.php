<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('email_templates')->updateOrInsert(
            ['name' => 'product_approved'],
            [
                'subject' => 'Your product has been approved!',
                'body' => '<p>Hi {{ user_name }},</p><p>Congratulations! Your product, {{ product_name }}, has been approved and is now live on our site.</p><p>It will be published on {{ product_publish_datetime }}.</p><p>You can view it here: <a href="{{ product_url }}">{{ product_url }}</a></p><p>Thanks,</p><p>{{ site_name }}</p>',
                'is_html' => true,
                'from_name' => config('mail.from.name'),
                'from_email' => config('mail.from.address'),
                'reply_to_email' => config('mail.from.address'),
                'allowed_variables' => json_encode(['user_name', 'product_name', 'product_url', 'site_name', 'product_publish_datetime']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
