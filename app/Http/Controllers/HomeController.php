<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\HomepageContent;
use App\Models\ServiceListing;
use App\Models\Testimonial;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Collection;
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
            'featuredServices' => $this->loadFeaturedServices(),
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
            return [];
        }

        try {
            $testimonials = Testimonial::query()
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->get();

            if ($testimonials->isEmpty()) {
                return [];
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
            return [];
        }
    }

    private function loadFeaturedServices(): Collection
    {
        if (! Schema::hasTable('service_listings')) {
            return collect();
        }

        try {
            return ServiceListing::query()
                ->public()
                ->with('category')
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->limit(4)
                ->get();
        } catch (\Throwable) {
            return collect();
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

                if ($section?->metadata && is_array($section->metadata) && count($section->metadata) > 0) {
                    return $section->metadata;
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        return [];
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

    private function fallbackFaqs(): array
    {
        return [
            ['question' => 'How does PAJPYS verify providers?', 'answer' => 'Providers can complete identity verification, skills assessment, and profile review before offering services or accepting jobs on the marketplace.'],
            ['question' => 'How are payments handled?', 'answer' => 'Funds are held in escrow when you hire talent on PAJPYS. Payment is released only when you approve completed work. All prices default to TTD; USD and other currencies can be configured in the backend where needed.'],
            ['question' => 'What if I\'m not satisfied with the work?', 'answer' => 'You can request revisions within the job scope. If issues persist, our dispute resolution team mediates fairly. Unreleased escrow funds remain protected until resolution.'],
            ['question' => 'Can I hire part-time or for a single project?', 'answer' => 'Yes. Post one-off tasks, ongoing hourly work, or retainer arrangements. Providers set their availability and you agree on scope before work begins.'],
            ['question' => 'Which Caribbean countries do you serve?', 'answer' => 'PAJPYS operates across Jamaica, Trinidad & Tobago, Barbados, Bahamas, Guyana, and other Caribbean territories — with talent working in your timezone.'],
            ['question' => 'Is there a fee to join as a client?', 'answer' => 'Creating an account is free. Job postings require a one-time posting fee. We charge a transparent platform fee on completed transactions, which covers escrow, dispute support, and payment processing.'],
        ];
    }
}
