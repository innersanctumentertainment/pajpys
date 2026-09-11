<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesAndSkillsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Administrative Support' => [
                'Data Entry', 'Calendar Management', 'Email Management', 'Travel Planning', 'Document Preparation',
            ],
            'Customer Service' => [
                'Live Chat Support', 'Phone Support', 'Ticket Management', 'CRM Management', 'Customer Follow-up',
            ],
            'Social Media' => [
                'Content Scheduling', 'Community Management', 'Social Media Analytics', 'Influencer Outreach', 'Ad Campaign Support',
            ],
            'Content & Writing' => [
                'Blog Writing', 'Copywriting', 'Proofreading', 'Transcription', 'Research Writing',
            ],
            'Bookkeeping & Finance' => [
                'Invoice Processing', 'Expense Tracking', 'Accounts Receivable', 'Accounts Payable', 'Financial Reporting',
            ],
            'Marketing' => [
                'Email Marketing', 'SEO Support', 'Market Research', 'Lead Generation', 'Campaign Coordination',
            ],
            'Technical Support' => [
                'Website Updates', 'WordPress Management', 'Basic IT Support', 'Software Troubleshooting', 'Database Maintenance',
            ],
            'Creative Services' => [
                'Graphic Design', 'Video Editing', 'Presentation Design', 'Brand Asset Management', 'Canva Design',
            ],
        ];

        $now = now();
        $sortOrder = 0;

        foreach ($categories as $categoryName => $skills) {
            $slug = Str::slug($categoryName);

            DB::table('categories')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $categoryName,
                    'slug' => $slug,
                    'description' => "Services related to {$categoryName}.",
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $categoryId = DB::table('categories')->where('slug', $slug)->value('id');

            foreach ($skills as $index => $skillName) {
                $skillSlug = Str::slug($skillName);

                DB::table('skills')->updateOrInsert(
                    ['slug' => $skillSlug],
                    [
                        'category_id' => $categoryId,
                        'name' => $skillName,
                        'slug' => $skillSlug,
                        'is_active' => true,
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $sortOrder++;
        }
    }
}
