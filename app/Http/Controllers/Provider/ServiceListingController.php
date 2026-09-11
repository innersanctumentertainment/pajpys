<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\Skill;
use App\Services\PlatformSettingsService;
use App\Services\ServiceListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceListingController extends Controller
{
    public function __construct(
        private readonly ServiceListingService $serviceListings,
        private readonly PlatformSettingsService $settings,
    ) {}

    public function index(Request $request): View
    {
        $listings = ServiceListing::query()
            ->where('user_id', $request->user()->id)
            ->with('category')
            ->latest()
            ->paginate(20);

        return view('provider.services.index', [
            'listings' => $listings,
            'active' => 'provider.services',
        ]);
    }

    public function create(Request $request): View
    {
        return view('provider.services.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'skills' => Skill::query()->orderBy('name')->get(),
            'defaultCurrency' => $this->settings->defaultCurrency(),
            'allowedCurrencies' => $this->settings->allowedCurrencies(),
            'active' => 'provider.services',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'deliverables' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'service_type' => ['required', 'in:general,virtual_assistant,professional,creative,technical'],
            'pricing_type' => ['required', 'in:fixed,hourly,negotiable'],
            'price_amount' => ['nullable', 'numeric', 'min:0'],
            'price_max_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'in:'.implode(',', $this->settings->allowedCurrencies())],
            'delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_va_service' => ['boolean'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['exists:skills,id'],
        ]);

        $listing = $this->serviceListings->create($request->user(), $validated);

        return redirect()
            ->route('provider.services.show', $listing)
            ->with('success', 'Service listing saved as draft.');
    }

    public function show(Request $request, ServiceListing $service): View
    {
        abort_unless($service->user_id === $request->user()->id, 403);

        return view('provider.services.show', [
            'listing' => $service->load(['category', 'skills']),
            'active' => 'provider.services',
        ]);
    }

    public function publish(Request $request, ServiceListing $service): RedirectResponse
    {
        abort_unless($service->user_id === $request->user()->id, 403);

        $this->serviceListings->publish($service, $request->user());

        return redirect()
            ->route('provider.services.show', $service)
            ->with('success', 'Your service is now live on PAJPYS.');
    }
}
