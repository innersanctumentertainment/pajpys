<?php

namespace App\Services;

use App\Enums\ServiceListingStatus;
use App\Models\ServiceListing;
use App\Models\User;
use Illuminate\Support\Str;

class ServiceListingService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly NotificationService $notifications,
        private readonly PlatformSettingsService $settings,
    ) {}

    public function create(User $provider, array $data): ServiceListing
    {
        $listing = ServiceListing::query()->create([
            'user_id' => $provider->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'],
            'deliverables' => $data['deliverables'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'service_type' => $data['service_type'] ?? 'general',
            'pricing_type' => $data['pricing_type'] ?? 'fixed',
            'price_amount' => $data['price_amount'] ?? null,
            'price_amount_minor' => isset($data['price_amount_minor'])
                ? (int) $data['price_amount_minor']
                : (isset($data['price_amount']) ? (int) round((float) $data['price_amount'] * 100) : null),
            'price_max_amount' => $data['price_max_amount'] ?? null,
            'price_max_amount_minor' => isset($data['price_max_amount'])
                ? (int) round((float) $data['price_max_amount'] * 100)
                : null,
            'currency' => strtoupper($data['currency'] ?? $this->settings->defaultCurrency()),
            'delivery_days' => $data['delivery_days'] ?? null,
            'is_va_service' => (bool) ($data['is_va_service'] ?? false),
            'status' => ServiceListingStatus::Draft,
        ]);

        if (! empty($data['skill_ids'])) {
            $listing->skills()->sync($data['skill_ids']);
        }

        $this->auditLog->log('service_listing.created', $listing, user: $provider);

        return $listing->load(['category', 'skills']);
    }

    public function publish(ServiceListing $listing, User $provider): ServiceListing
    {
        $listing->update([
            'status' => ServiceListingStatus::Active,
            'published_at' => now(),
        ]);

        $this->auditLog->log('service_listing.published', $listing, user: $provider);

        return $listing->fresh();
    }

    public function recordInquiry(ServiceListing $listing, User $client, string $message): void
    {
        $inquiry = $listing->inquiries()->create([
            'client_id' => $client->id,
            'provider_id' => $listing->user_id,
            'message' => $message,
            'status' => 'pending',
        ]);

        $listing->increment('inquiry_count');

        $this->notifications->notify(
            $listing->provider,
            'service.inquiry',
            'New service inquiry',
            "{$client->name} inquired about your service: {$listing->title}",
            ['service_listing_id' => $listing->id, 'inquiry_id' => $inquiry->id],
        );

        $this->auditLog->log('service_inquiry.created', $inquiry, user: $client);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (ServiceListing::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
