<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\FeaturedVa;
use App\Models\HomepageContent;
use App\Models\Testimonial;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
    ) {}

    public function index(): View
    {
        $postingFeeAmount = (float) $this->settings->get('job_posting_fee', 20);
        $postingFeeCurrency = strtoupper((string) $this->settings->get('job_posting_fee_currency', 'TTD'));

        return view('home', [
            'categories' => $this->loadCategories(),
            'testimonials' => $this->loadTestimonials(),
            'featuredVas' => $this->loadFeaturedVas(),
            'faqs' => $this->loadFaqs(),
            'stats' => $this->loadStats(),
            'heroContent' => $this->loadSection('hero'),
            'defaultCurrency' => $this->settings->defaultCurrency(),
            'postingFeeLabel' => $this->settings->formatMoney($postingFeeAmount, $postingFeeCurrency),
            'postingFeeAmount' => $postingFeeAmount,
            'postingFeeCurrency' => $postingFeeCurrency,
        ]);
    }

    private function loadCategories(): array
    {
        if (! Schema::hasTable('categories')) {
            return $this->fallbackCategories();
        }

        try {
            $categories = Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->limit(8)
                ->get();

            if ($categories->isEmpty()) {
                return $this->fallbackCategories();
            }

            return $categories->map(fn ($c) => [
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'icon' => $c->icon ?? $this->defaultIcon($c->slug),
            ])->all();
        } catch (\Throwable) {
            return $this->fallbackCategories();
        }
    }

    private function loadTestimonials(): array
    {
        if (! Schema::hasTable('testimonials')) {
            return $this->fallbackTestimonials();
        }

        try {
            $testimonials = Testimonial::query()
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->get();

            if ($testimonials->isEmpty()) {
                return $this->fallbackTestimonials();
            }

            return $testimonials->map(fn ($t) => [
                'author' => $t->author_name,
                'title' => $t->author_title,
                'company' => $t->author_company,
                'content' => $t->content,
                'rating' => $t->rating ?? 5,
                'avatar' => $t->avatar_path,
            ])->all();
        } catch (\Throwable) {
            return $this->fallbackTestimonials();
        }
    }

    private function loadFeaturedVas(): array
    {
        if (! Schema::hasTable('featured_vas')) {
            return $this->fallbackFeaturedVas();
        }

        try {
            $featured = FeaturedVa::query()
                ->active()
                ->with('vaProfile')
                ->orderBy('sort_order')
                ->limit(4)
                ->get()
                ->filter(fn ($f) => $f->vaProfile !== null);

            if ($featured->isEmpty()) {
                return $this->fallbackFeaturedVas();
            }

            return $featured->map(fn ($f) => [
                'name' => $f->vaProfile->display_name,
                'headline' => $f->vaProfile->headline,
                'rating' => $f->vaProfile->average_rating ?? 4.9,
                'jobs' => $f->vaProfile->completed_jobs_count ?? 0,
                'rate' => $f->vaProfile->hourly_rate_min,
                'currency' => $f->vaProfile->currency ?? 'TTD',
                'verified' => $f->vaProfile->is_verified,
                'avatar' => $f->vaProfile->avatar_path,
            ])->all();
        } catch (\Throwable) {
            return $this->fallbackFeaturedVas();
        }
    }

    private function loadFaqs(): array
    {
        if (! Schema::hasTable('faqs')) {
            return $this->fallbackFaqs();
        }

        try {
            $faqs = Faq::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->limit(8)
                ->get();

            if ($faqs->isEmpty()) {
                return $this->fallbackFaqs();
            }

            return $faqs->map(fn ($f) => [
                'question' => $f->question,
                'answer' => $f->answer,
            ])->all();
        } catch (\Throwable) {
            return $this->fallbackFaqs();
        }
    }

    private function loadStats(): array
    {
        if (Schema::hasTable('homepage_content')) {
            try {
                $section = HomepageContent::query()
                    ->where('section_key', 'stats')
                    ->where('is_active', true)
                    ->first();

                if ($section?->metadata) {
                    return $section->metadata;
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        return [
            ['value' => '2,400+', 'label' => 'Verified VAs across the Caribbean'],
            ['value' => '18', 'label' => 'Island markets served'],
            ['value' => '98%', 'label' => 'Client satisfaction rate'],
            ['value' => '$4.2M', 'label' => 'Paid to Caribbean talent'],
        ];
    }

    private function loadSection(string $key): ?array
    {
        if (! Schema::hasTable('homepage_content')) {
            return null;
        }

        try {
            $section = HomepageContent::query()
                ->where('section_key', $key)
                ->where('is_active', true)
                ->first();

            if (! $section) {
                return null;
            }

            return [
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'body' => $section->body,
                'metadata' => $section->metadata,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function defaultIcon(string $slug): string
    {
        return match (true) {
            str_contains($slug, 'admin') => '📋',
            str_contains($slug, 'book') => '📅',
            str_contains($slug, 'social') => '📱',
            str_contains($slug, 'customer') => '💬',
            str_contains($slug, 'data') => '📊',
            str_contains($slug, 'design') => '🎨',
            str_contains($slug, 'tech') => '💻',
            default => '✨',
        };
    }

    private function fallbackCategories(): array
    {
        return [
            ['name' => 'Executive Assistance', 'slug' => 'executive-assistance', 'description' => 'Calendar management, inbox triage, travel coordination, and executive-level support.', 'icon' => '👔'],
            ['name' => 'Customer Support', 'slug' => 'customer-support', 'description' => 'Email, chat, and phone support with Caribbean warmth and professionalism.', 'icon' => '💬'],
            ['name' => 'Social Media', 'slug' => 'social-media', 'description' => 'Content scheduling, community management, and brand voice across platforms.', 'icon' => '📱'],
            ['name' => 'Bookkeeping', 'slug' => 'bookkeeping', 'description' => 'Invoicing, expense tracking, reconciliation, and financial reporting.', 'icon' => '📊'],
            ['name' => 'Data Entry', 'slug' => 'data-entry', 'description' => 'Accurate data processing, CRM updates, and spreadsheet management.', 'icon' => '📝'],
            ['name' => 'Creative Design', 'slug' => 'creative-design', 'description' => 'Canva graphics, presentations, brand assets, and marketing collateral.', 'icon' => '🎨'],
            ['name' => 'Research', 'slug' => 'research', 'description' => 'Market research, competitor analysis, lead generation, and due diligence.', 'icon' => '🔍'],
            ['name' => 'Project Management', 'slug' => 'project-management', 'description' => 'Task coordination, milestone tracking, and team communication.', 'icon' => '🗂️'],
        ];
    }

    private function fallbackTestimonials(): array
    {
        return [
            [
                'author' => 'Marcus Williams',
                'title' => 'Founder',
                'company' => 'Island Fresh Exports',
                'content' => 'PAJPYS connected us with a VA in Kingston who handles our entire order pipeline. We cut admin time by 60% and finally have weekends back.',
                'rating' => 5,
                'avatar' => null,
            ],
            [
                'author' => 'Dr. Aisha Mohammed',
                'title' => 'Clinic Director',
                'company' => 'Carib Wellness Group',
                'content' => 'The vetting process gave us confidence. Our virtual assistant manages patient scheduling across three locations with zero missed appointments.',
                'rating' => 5,
                'avatar' => null,
            ],
            [
                'author' => 'James O\'Connor',
                'title' => 'CEO',
                'company' => 'TradeWind Logistics',
                'content' => 'Escrow payments and milestone tracking mean we only pay for completed work. It\'s the safest way we\'ve found to hire Caribbean talent remotely.',
                'rating' => 5,
                'avatar' => null,
            ],
        ];
    }

    private function fallbackFeaturedVas(): array
    {
        return [
            ['name' => 'Keisha Thompson', 'headline' => 'Executive VA · 8 yrs experience', 'rating' => 4.98, 'jobs' => 142, 'rate' => 120, 'currency' => 'TTD', 'verified' => true, 'avatar' => null],
            ['name' => 'Andre Baptiste', 'headline' => 'Bookkeeping & Finance Specialist', 'rating' => 4.95, 'jobs' => 89, 'rate' => 150, 'currency' => 'TTD', 'verified' => true, 'avatar' => null],
            ['name' => 'Soraya Ali', 'headline' => 'Social Media & Content Strategist', 'rating' => 4.97, 'jobs' => 116, 'rate' => 110, 'currency' => 'TTD', 'verified' => true, 'avatar' => null],
            ['name' => 'David Clarke', 'headline' => 'Customer Success & CRM Expert', 'rating' => 4.96, 'jobs' => 203, 'rate' => 100, 'currency' => 'TTD', 'verified' => true, 'avatar' => null],
        ];
    }

    private function fallbackFaqs(): array
    {
        return [
            ['question' => 'How does PAJPYS vet virtual assistants?', 'answer' => 'Every VA completes identity verification, skills assessment, a background check, and a live interview. Only the top 15% of applicants are approved to join the marketplace.'],
            ['question' => 'How are payments handled?', 'answer' => 'Funds are held in escrow when you hire talent on PAJPYS. Payment is released only when you approve completed work. All prices default to TTD; USD and other currencies can be configured in the backend where needed.'],
            ['question' => 'What if I\'m not satisfied with the work?', 'answer' => 'You can request revisions within the job scope. If issues persist, our dispute resolution team mediates fairly. Unreleased escrow funds remain protected until resolution.'],
            ['question' => 'Can I hire part-time or for a single project?', 'answer' => 'Yes. Post one-off tasks, ongoing hourly work, or retainer arrangements. VAs set their availability and you agree on scope before work begins.'],
            ['question' => 'Which Caribbean countries do you serve?', 'answer' => 'We operate across Jamaica, Trinidad & Tobago, Barbados, Bahamas, Guyana, and 13 more Caribbean territories—with VAs working in your timezone.'],
            ['question' => 'Is there a fee to join as a client?', 'answer' => 'Creating an account is free. We charge a transparent 12% platform fee on completed transactions, which covers escrow, dispute support, and payment processing.'],
        ];
    }
}
