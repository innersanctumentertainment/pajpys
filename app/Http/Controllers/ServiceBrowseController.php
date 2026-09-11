<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\Skill;
use App\Services\ServiceListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceBrowseController extends Controller
{
    public function __construct(
        private readonly ServiceListingService $serviceListings,
    ) {}

    public function index(Request $request): View
    {
        $query = ServiceListing::query()
            ->public()
            ->with(['provider.providerProfile', 'category', 'skills']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
        }

        if ($request->filled('type')) {
            $query->where('service_type', $request->string('type'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)->orWhere('description', 'like', $term);
            });
        }

        if ($request->boolean('va_only')) {
            $query->where('is_va_service', true);
        }

        $sort = $request->string('sort', 'newest');
        match ($sort) {
            'price_low' => $query->orderBy('price_amount_minor'),
            'price_high' => $query->orderByDesc('price_amount_minor'),
            default => $query->latest('published_at'),
        };

        return view('services.browse', [
            'services' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'skills' => Skill::query()->orderBy('name')->limit(20)->get(),
            'filters' => $request->only(['category', 'type', 'q', 'va_only', 'sort']),
        ]);
    }

    public function show(ServiceListing $service): View
    {
        abort_unless($service->status->value === 'active' && $service->published_at !== null, 404);

        $service->increment('view_count');
        $service->load(['provider.providerProfile', 'category', 'skills']);

        return view('services.show', [
            'service' => $service,
        ]);
    }

    public function inquire(Request $request, ServiceListing $service): RedirectResponse
    {
        abort_unless($service->status->value === 'active', 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $this->serviceListings->recordInquiry($service, $request->user(), $validated['message']);

        return back()->with('success', 'Your inquiry was sent to the provider.');
    }
}
