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

    private function loadCategories(): array { return $this->fallbackCategories(); }
    private function loadTestimonials(): array { return $this->fallbackTestimonials(); }
    private function loadFeaturedVas(): array { return $this->fallbackFeaturedVas(); }
    private function loadFaqs(): array { return $this->fallbackFaqs(); }
    private function loadStats(): array {
        return [
            ['value' => '2,400+', 'label' => 'Verified VAs across the Caribbean'],
            ['value' => '18', 'label' => 'Island markets served'],
            ['value' => '98%', 'label' => 'Client satisfaction rate'],
            ['value' => '$4.2M', 'label' => 'Paid to Caribbean talent'],
        ];
    }
    private function loadSection(string $key): ?array { return null; }

    private function fallbackCategories(): array {
        return [
            ['name' => 'Executive Assistance', 'slug' => 'executive-assistance', 'description' => 'Calendar management, inbox triage, travel coordination, and executive-level support.', 'icon' => '👔'],
        ];
    }
    private function fallbackTestimonials(): array {
        return [
            ['author' => 'Marcus Williams', 'title' => 'Founder', 'company' => 'Island Fresh Exports', 'content' => 'PAJPYS connected us with a VA in Kingston who handles our entire order pipeline.', 'rating' => 5, 'avatar' => null],
        ];
    }
    private function fallbackFeaturedVas(): array {
        return [
            ['name' => 'Keisha Thompson', 'headline' => 'Executive VA · 8 yrs experience', 'rating' => 4.98, 'jobs' => 142, 'rate' => 120, 'currency' => 'TTD', 'verified' => true, 'avatar' => null],
        ];
    }
    private function fallbackFaqs(): array {
        return [
            ['question' => 'How does PAJPYS vet virtual assistants?', 'answer' => 'Every VA completes identity verification, skills assessment, a background check, and a live interview.'],
        ];
    }
}
