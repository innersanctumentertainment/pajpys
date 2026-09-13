@extends('layouts.app')

@section('title', 'PAJPYS')
@section('meta_description', 'PAJPYS — Post a job. Post your services. Caribbean-first marketplace with escrow-protected payments and verified talent.')

@section('content')
<div class="gradient-hero min-h-screen">
    {{-- Navigation --}}
    <header class="sticky top-0 z-40 backdrop-blur-md bg-ocean-deep/70 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <a href="{{ url('/') }}" class="font-display text-2xl font-bold text-pearl tracking-tight">
                    PA<span class="text-teal">JPYS</span>
                </a>

                <nav class="hidden md:flex items-center gap-8" aria-label="Main navigation">
                    <a href="#how-it-works" class="nav-link">How it works</a>
                    <a href="{{ route('services.browse') }}" class="nav-link">Browse services</a>
                    <a href="#categories" class="nav-link">Categories</a>
                    @if ($featuredServices->isNotEmpty())
                        <a href="#featured" class="nav-link">Featured</a>
                    @endif
                    <a href="#pricing" class="nav-link">Pricing</a>
                    <a href="#faq" class="nav-link">FAQ</a>
                </nav>

                <div class="hidden md:flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <x-button variant="secondary" href="{{ url('/dashboard') }}">Dashboard</x-button>
                        @else
                            <a href="{{ route('login') }}" class="nav-link px-3 py-2">Log in</a>
                            @if (Route::has('register'))
                                <x-button href="{{ route('register') }}">Get started</x-button>
                            @else
                                <x-button href="#how-it-works">Get started</x-button>
                            @endif
                        @endauth
                    @else
                        <x-button href="#how-it-works">Get started</x-button>
                    @endif
                </div>

                <button id="mobile-nav-toggle" class="md:hidden btn-secondary !p-2" aria-label="Open menu" aria-expanded="false">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-nav-menu" class="hidden md:hidden border-t border-white/5 bg-ocean-deep/95 backdrop-blur-lg">
            <nav class="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-3" aria-label="Mobile navigation">
                <a href="#how-it-works" class="nav-link py-2">How it works</a>
                <a href="#categories" class="nav-link py-2">Categories</a>
                <a href="#featured" class="nav-link py-2">Top VAs</a>
                <a href="#pricing" class="nav-link py-2">Pricing</a>
                <a href="#faq" class="nav-link py-2">FAQ</a>
                <div class="pt-3 border-t border-white/10 flex flex-col gap-2">
                    <x-button href="#how-it-works" class="w-full justify-center">Get started free</x-button>
                </div>
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute top-20 right-10 w-72 h-72 bg-teal/10 rounded-full blur-3xl animate-float"></div>
            <div class="absolute bottom-10 left-10 w-96 h-96 bg-coral/8 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-24 lg:pt-24 lg:pb-32">
            <div class="max-w-3xl">
                <x-badge class="mb-6">Post A Job · Post Your Services</x-badge>

                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-pearl leading-[1.1] tracking-tight mb-6">
                    @if($heroContent['title'] ?? null)
                        {{ $heroContent['title'] }}
                    @else
                        Post a job. Post your services.<br>
                        <span class="bg-gradient-to-r from-teal to-coral-light bg-clip-text text-transparent">Connect. Work. Get paid.</span>
                    @endif
                </h1>

                <p class="text-lg sm:text-xl text-pearl/70 leading-relaxed mb-10 max-w-2xl">
                    {{ $heroContent['subtitle'] ?? 'PAJPYS is the Caribbean-first marketplace where clients post jobs and professionals post services — including virtual assistants, creatives, and specialists. Secure payments. Trusted talent.' }}
                </p>

                <div class="flex flex-col sm:flex-row gap-4 mb-12">
                    @auth
                        <x-button href="{{ route('client.jobs.index') }}" class="text-base px-8 py-3.5">Post a job — {{ $postingFeeLabel ?? 'TTD $20.00' }}</x-button>
                        <x-button variant="secondary" href="{{ route('provider.services.create') }}" class="text-base px-8 py-3.5">Post your service</x-button>
                    @else
                        <x-button href="{{ route('register') }}" class="text-base px-8 py-3.5">Post a job — {{ $postingFeeLabel ?? 'TTD $20.00' }}</x-button>
                        <x-button variant="secondary" href="{{ route('services.browse') }}" class="text-base px-8 py-3.5">Browse services</x-button>
                    @endauth
                </div>

                <div class="flex flex-wrap items-center gap-6 text-sm text-pearl/50">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Jobs &amp; services marketplace
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Escrow-protected payments
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        15% withdrawal fee
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="py-20 lg:py-28 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <x-badge class="mb-4">Simple process</x-badge>
                <h2 class="section-heading text-pearl mb-4">How PAJPYS works</h2>
                <p class="section-subheading mx-auto">Two sides of one marketplace — post jobs or post your services. Virtual assistants are a core part of the platform.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                @foreach([
                    ['step' => '1', 'title' => 'Post a job or service', 'desc' => 'Clients pay a '.($postingFeeLabel ?? 'TTD $20.00').' posting fee per job. Providers list services for free — including virtual assistant offerings.'],
                    ['step' => '2', 'title' => 'Connect & match', 'desc' => 'Browse services, accept job opportunities, or get matched with the right talent automatically.'],
                    ['step' => '3', 'title' => 'Work securely', 'desc' => 'Communicate in-platform, track deliverables, and keep every agreement on record.'],
                    ['step' => '4', 'title' => 'Get paid', 'desc' => 'Funds release after approval. Withdraw to your bank with a transparent 15% platform fee.'],
                ] as $item)
                    <x-card class="relative group hover:border-teal/30 transition-colors duration-300">
                        <div class="step-number mb-4">{{ $item['step'] }}</div>
                        <h3 class="font-display text-lg font-semibold text-pearl mb-2">{{ $item['title'] }}</h3>
                        <p class="text-sm text-pearl/60 leading-relaxed">{{ $item['desc'] }}</p>
                    </x-card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Categories --}}
    <section id="categories" class="py-20 lg:py-28 bg-ocean-mid/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-12">
                <div>
                    <x-badge variant="coral" class="mb-4">Browse talent</x-badge>
                    <h2 class="section-heading text-pearl mb-4">Popular categories</h2>
                    <p class="section-subheading">Find specialists across the skills Caribbean businesses need most.</p>
                </div>
                <x-button variant="secondary" href="#">View all categories</x-button>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                @foreach($categories as $category)
                    <a href="#" class="glass-card p-5 hover:border-teal/30 hover:bg-white/8 transition-all duration-300 group block">
                        <span class="text-2xl mb-3 block" aria-hidden="true">{{ $category['icon'] }}</span>
                        <h3 class="font-display font-semibold text-pearl group-hover:text-teal transition-colors mb-1">{{ $category['name'] }}</h3>
                        <p class="text-xs text-pearl/50 leading-relaxed line-clamp-2">{{ $category['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($featuredServices->isNotEmpty())
        {{-- Featured services --}}
        <section id="featured" class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <x-badge variant="verified" class="mb-4">✓ Featured</x-badge>
                    <h2 class="section-heading text-pearl mb-4">Featured services</h2>
                    <p class="section-subheading mx-auto">Browse professional services posted on PAJPYS.</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($featuredServices as $service)
                        <a href="{{ route('services.show', $service) }}" class="glass-card p-6 block hover:border-teal/30 transition-colors group">
                            @if ($service->category)
                                <x-badge class="mb-3">{{ $service->category->name }}</x-badge>
                            @endif
                            <h3 class="font-display font-semibold text-pearl group-hover:text-teal transition-colors mb-2">{{ $service->title }}</h3>
                            <p class="text-xs text-pearl/50 line-clamp-3">{{ Str::limit($service->description, 100) }}</p>
                            @if ($service->price_amount_minor)
                                <p class="mt-4 text-teal font-semibold text-sm">
                                    From {{ $service->currency }} {{ number_format($service->price_amount_minor / 100, 2) }}
                                </p>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="text-center mt-10">
                    <x-button variant="secondary" href="{{ route('services.browse') }}">Browse all services</x-button>
                </div>
            </div>
        </section>
    @endif

    {{-- Trust & Security --}}
    <section class="py-20 lg:py-28 border-t border-white/5 bg-ocean-mid/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div>
                    <x-badge class="mb-4">Trust & security</x-badge>
                    <h2 class="section-heading text-pearl mb-6">Your business, protected at every step</h2>
                    <p class="text-pearl/65 leading-relaxed mb-8">PAJPYS was built for Caribbean businesses that need reliable remote support without the risk. Every transaction is escrow-protected, providers can be verified, and every dispute is handled fairly.</p>

                    <ul class="space-y-4">
                        @foreach([
                            ['icon' => '🔒', 'title' => 'Escrow payments', 'desc' => 'Funds held securely until you approve completed work.'],
                            ['icon' => '🛡️', 'title' => 'Identity verification', 'desc' => 'Optional verification for providers who want to build trust on the platform.'],
                            ['icon' => '⚖️', 'title' => 'Dispute resolution', 'desc' => 'Dedicated team mediates fairly when issues arise.'],
                            ['icon' => '🔐', 'title' => 'Data encryption', 'desc' => 'Bank-grade encryption for messages, files, and payments.'],
                        ] as $feature)
                            <li class="flex gap-4">
                                <span class="text-xl shrink-0" aria-hidden="true">{{ $feature['icon'] }}</span>
                                <div>
                                    <h4 class="font-display font-semibold text-pearl text-sm">{{ $feature['title'] }}</h4>
                                    <p class="text-sm text-pearl/50">{{ $feature['desc'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if (count($stats) > 0)
                    <div class="glass-card p-8 lg:p-10">
                        <div class="grid grid-cols-2 gap-6">
                            @foreach ($stats as $stat)
                                <div class="text-center p-4">
                                    <div class="font-display text-3xl lg:text-4xl font-bold text-teal mb-1">{{ $stat['value'] }}</div>
                                    <div class="text-xs text-pearl/50 leading-snug">{{ $stat['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="glass-card p-8 lg:p-10 text-center">
                        <p class="font-display text-xl font-semibold text-pearl mb-2">Built for the Caribbean</p>
                        <p class="text-sm text-pearl/60 leading-relaxed">Connect with local talent, pay securely, and grow your business on PAJPYS.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if (count($testimonials) > 0)
        {{-- Testimonials --}}
        <section class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <x-badge variant="coral" class="mb-4">Client stories</x-badge>
                    <h2 class="section-heading text-pearl mb-4">Trusted by Caribbean businesses</h2>
                    <p class="section-subheading mx-auto">Real results from companies who hired through PAJPYS.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
                    @foreach ($testimonials as $testimonial)
                        <x-card>
                            <div class="star-rating text-sm mb-4" aria-label="{{ $testimonial['rating'] }} out of 5 stars">
                                @for ($i = 0; $i < ($testimonial['rating'] ?? 5); $i++)★@endfor
                            </div>
                            <blockquote class="text-pearl/80 text-sm leading-relaxed mb-6">"{{ $testimonial['content'] }}"</blockquote>
                            <footer>
                                <cite class="not-italic">
                                    <div class="font-display font-semibold text-pearl text-sm">{{ $testimonial['author'] }}</div>
                                    <div class="text-xs text-pearl/50">{{ $testimonial['title'] }}{{ $testimonial['company'] ? ', ' . $testimonial['company'] : '' }}</div>
                                </cite>
                            </footer>
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Pricing --}}
    <section id="pricing" class="py-20 lg:py-28 border-t border-white/5 bg-ocean-mid/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <x-badge class="mb-4">Transparent pricing</x-badge>
                <h2 class="section-heading text-pearl mb-4">Simple, honest fees</h2>
                <p class="section-subheading mx-auto">Clear fees. No subscriptions. Post services free; jobs require a {{ $postingFeeLabel ?? 'TTD $20.00' }} posting fee.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 lg:gap-8 max-w-5xl mx-auto">
                <x-card class="md:col-span-1">
                    <h3 class="font-display text-lg font-semibold text-pearl mb-2">Post a job</h3>
                    <div class="font-display text-4xl font-bold text-teal mb-1">{{ $postingFeeCurrency ?? 'TTD' }} ${{ number_format($postingFeeAmount ?? 20, 0) }}</div>
                    <p class="text-xs text-pearl/50 mb-6">per job posting (default currency)</p>
                    <ul class="space-y-3 text-sm text-pearl/70">
                        <li class="flex gap-2"><span class="text-teal">✓</span> Reach providers &amp; VAs</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Escrow-protected budget</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Smart candidate matching</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Plus job budget funding</li>
                    </ul>
                </x-card>

                <x-card class="md:col-span-1 border-teal/30 !bg-teal/5">
                    <x-badge class="mb-3">Post Your Services</x-badge>
                    <h3 class="font-display text-lg font-semibold text-pearl mb-2">List a service</h3>
                    <div class="font-display text-4xl font-bold text-teal mb-1">Free</div>
                    <p class="text-xs text-pearl/50 mb-6">to publish your offering</p>
                    <ul class="space-y-3 text-sm text-pearl/70">
                        <li class="flex gap-2"><span class="text-teal">✓</span> VA &amp; professional services</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Receive client inquiries</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Set your own pricing</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Build your reputation</li>
                    </ul>
                </x-card>

                <x-card class="md:col-span-1">
                    <h3 class="font-display text-lg font-semibold text-pearl mb-2">Withdrawals</h3>
                    <div class="font-display text-4xl font-bold text-coral-light mb-1">15%</div>
                    <p class="text-xs text-pearl/50 mb-6">platform fee on withdrawals</p>
                    <ul class="space-y-3 text-sm text-pearl/70">
                        <li class="flex gap-2"><span class="text-teal">✓</span> Same rate for everyone</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Secure bank payouts</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> TTD default · USD optional in admin</li>
                        <li class="flex gap-2"><span class="text-teal">✓</span> Full transaction history</li>
                    </ul>
                </x-card>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="py-20 lg:py-28">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <x-badge class="mb-4">FAQ</x-badge>
                <h2 class="section-heading text-pearl mb-4">Common questions</h2>
            </div>

            <div class="space-y-3">
                @foreach($faqs as $index => $faq)
                    <details class="faq-item glass-card group" @if($index === 0) open @endif>
                        <summary class="flex items-center justify-between p-5 font-display font-medium text-pearl cursor-pointer">
                            {{ $faq['question'] }}
                            <svg class="w-5 h-5 text-pearl/40 group-open:rotate-180 transition-transform shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="px-5 pb-5 text-sm text-pearl/65 leading-relaxed border-t border-white/5 pt-4">
                            {{ $faq['answer'] }}
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-28 border-t border-white/5">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="font-display text-3xl sm:text-4xl font-bold text-pearl mb-4">Ready to get things done?</h2>
            <p class="text-pearl/60 mb-8 max-w-xl mx-auto">Join thousands of Caribbean businesses and freelancers building the future of remote work — together.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <x-button href="#" class="text-base px-8 py-3.5">Post your first job — it's free</x-button>
                <x-button variant="secondary" href="#" class="text-base px-8 py-3.5">Become a virtual assistant</x-button>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-white/5 bg-ocean-deep/80 py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 mb-12">
                <div class="sm:col-span-2 lg:col-span-1">
                    <a href="{{ url('/') }}" class="font-display text-xl font-bold text-pearl tracking-tight">
                        Assist<span class="text-teal">Carib</span>
                    </a>
                    <p class="text-sm text-pearl/50 mt-3 leading-relaxed">Hire the right virtual assistant. Get things done.</p>
                </div>

                <div>
                    <h4 class="font-display font-semibold text-pearl text-sm mb-4">Platform</h4>
                    <ul class="space-y-2 text-sm text-pearl/50">
                        <li><a href="#how-it-works" class="hover:text-teal transition-colors">How it works</a></li>
                        <li><a href="#categories" class="hover:text-teal transition-colors">Categories</a></li>
                        <li><a href="#pricing" class="hover:text-teal transition-colors">Pricing</a></li>
                        <li><a href="#faq" class="hover:text-teal transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-display font-semibold text-pearl text-sm mb-4">Company</h4>
                    <ul class="space-y-2 text-sm text-pearl/50">
                        <li><a href="#" class="hover:text-teal transition-colors">About us</a></li>
                        <li><a href="#" class="hover:text-teal transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-teal transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-teal transition-colors">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-display font-semibold text-pearl text-sm mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm text-pearl/50">
                        <li><a href="#" class="hover:text-teal transition-colors">Terms of service</a></li>
                        <li><a href="#" class="hover:text-teal transition-colors">Privacy policy</a></li>
                        <li><a href="#" class="hover:text-teal transition-colors">Cookie policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-pearl/40">
                <p>&copy; {{ date('Y') }} PAJPYS. All rights reserved.</p>
                <p>Built for the Caribbean, by the Caribbean. 🌴</p>
            </div>
        </div>
    </footer>
</div>
@endsection
