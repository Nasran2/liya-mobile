@php
    use App\Models\Setting;
    use Illuminate\Support\Facades\Storage;

    $businessName = Setting::get('business_name', 'Liya Mobile') ?: 'Liya Mobile';
    $businessLogo = Setting::get('business_logo');
    $businessLogoUrl = $businessLogo ? Storage::url($businessLogo) : null;
    $businessPhone = Setting::get('business_phone', '076 746 4642') ?: '076 746 4642';
    $businessEmail = Setting::get('business_email', '');
    $businessAddress = Setting::get('business_address', 'Sri Lanka') ?: 'Sri Lanka';
    $whatsAppPhone = preg_replace('/\D+/', '', $businessPhone);

    if (str_starts_with($whatsAppPhone, '0')) {
        $whatsAppPhone = '94'.substr($whatsAppPhone, 1);
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head', ['title' => $title ?? $businessName])
        <meta name="description" content="{{ Setting::get('business_tagline', 'Phones, accessories, and mobile essentials from Liya Mobile.') }}">
    </head>
    <body class="min-h-screen overflow-x-hidden bg-white font-sans text-slate-950 antialiased selection:bg-blue-600 selection:text-white">
        <div class="bg-slate-950 text-white">
            <div class="mx-auto flex min-h-9 max-w-7xl items-center justify-center px-4 text-center text-[11px] font-bold tracking-wide sm:justify-between sm:px-6 lg:px-8">
                <p>Retail & wholesale mobile accessories</p>
                <div class="hidden items-center gap-5 text-white/60 sm:flex">
                    <a href="tel:{{ preg_replace('/\s+/', '', $businessPhone) }}" class="transition hover:text-white">{{ $businessPhone }}</a>
                    @if ($businessEmail)
                        <a href="mailto:{{ $businessEmail }}" class="transition hover:text-white">{{ $businessEmail }}</a>
                    @endif
                </div>
            </div>
        </div>

        <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
            <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-5 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" wire:navigate>
                    @if ($businessLogoUrl)
                        <img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}" class="size-10 rounded-xl border border-slate-200 bg-white object-contain p-1 shadow-sm" />
                    @else
                        <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-cyan-500 text-lg font-black text-white shadow-md shadow-blue-500/25">L</span>
                    @endif
                    <span class="truncate text-base font-black tracking-[-0.03em] text-slate-950 sm:text-lg">{{ $businessName }}</span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-600 lg:flex" aria-label="Main navigation">
                    <a href="{{ route('home') }}" class="relative text-blue-600 after:absolute after:bottom-[-6px] after:left-0 after:h-[2px] after:w-full after:rounded-full after:bg-blue-600 transition">Home</a>
                    <a href="#products" class="relative transition hover:text-blue-600 after:absolute after:bottom-[-6px] after:left-0 after:h-[2px] after:w-0 after:rounded-full after:bg-blue-600 hover:after:w-full after:transition-all">Shop</a>
                    <a href="#categories" class="relative transition hover:text-blue-600 after:absolute after:bottom-[-6px] after:left-0 after:h-[2px] after:w-0 after:rounded-full after:bg-blue-600 hover:after:w-full after:transition-all">Categories</a>
                    <a href="#why-us" class="relative transition hover:text-blue-600 after:absolute after:bottom-[-6px] after:left-0 after:h-[2px] after:w-0 after:rounded-full after:bg-blue-600 hover:after:w-full after:transition-all">Why us</a>
                    <a href="#contact" class="relative transition hover:text-blue-600 after:absolute after:bottom-[-6px] after:left-0 after:h-[2px] after:w-0 after:rounded-full after:bg-blue-600 hover:after:w-full after:transition-all">Contact</a>
                </nav>

                <div class="flex items-center gap-2">
                    <a href="#products" class="flex size-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-blue-600" aria-label="Search products">
                        <flux:icon.magnifying-glass class="size-5" />
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-600">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                            <span class="sm:hidden">Login</span>
                            <span class="hidden sm:inline">Staff login</span>
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        {{ $slot }}

        <footer id="contact" class="bg-slate-950 text-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-[1.3fr_0.7fr_0.7fr_1fr] lg:px-8 lg:py-18">
                <div class="max-w-sm">
                    <div class="flex items-center gap-3">
                        @if ($businessLogoUrl)
                            <img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}" class="size-11 rounded-xl bg-white object-contain p-1 shadow" />
                        @else
                            <span class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-cyan-500 text-lg font-black">L</span>
                        @endif
                        <span class="text-xl font-black tracking-tight">{{ $businessName }}</span>
                    </div>
                    <p class="mt-5 text-sm leading-6 text-white/50">{{ Setting::get('business_tagline', 'Your local destination for phones, accessories, retail offers, and wholesale deals.') }}</p>
                    <div class="mt-6 flex gap-2">
                        <a href="https://wa.me/{{ $whatsAppPhone }}" target="_blank" rel="noopener noreferrer" class="flex size-10 items-center justify-center rounded-full bg-white/8 text-white transition hover:bg-blue-600" aria-label="WhatsApp">
                            <flux:icon.chat-bubble-left-right class="size-5" />
                        </a>
                        <a href="tel:{{ preg_replace('/\s+/', '', $businessPhone) }}" class="flex size-10 items-center justify-center rounded-full bg-white/8 text-white transition hover:bg-blue-600" aria-label="Call us">
                            <flux:icon.phone class="size-5" />
                        </a>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-black uppercase tracking-[0.16em]">Shop</h2>
                    <ul class="mt-5 grid gap-3 text-sm text-white/50">
                        <li><a href="#products" class="transition hover:text-white">New arrivals</a></li>
                        <li><a href="#categories" class="transition hover:text-white">Categories</a></li>
                        <li><a href="#collections" class="transition hover:text-white">Featured collections</a></li>
                        <li><a href="#products" class="transition hover:text-white">All products</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-black uppercase tracking-[0.16em]">Company</h2>
                    <ul class="mt-5 grid gap-3 text-sm text-white/50">
                        <li><a href="#why-us" class="transition hover:text-white">Why choose us</a></li>
                        <li><a href="#contact" class="transition hover:text-white">Contact</a></li>
                        <li><a href="{{ route('login') }}" class="transition hover:text-white">Staff login</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-black uppercase tracking-[0.16em]">Visit or call</h2>
                    <div class="mt-5 grid gap-4 text-sm text-white/50">
                        <p class="flex gap-3"><flux:icon.map-pin class="mt-0.5 size-4 shrink-0 text-blue-400" />{{ $businessAddress }}</p>
                        <a href="tel:{{ preg_replace('/\s+/', '', $businessPhone) }}" class="flex gap-3 transition hover:text-white"><flux:icon.phone class="mt-0.5 size-4 shrink-0 text-blue-400" />{{ $businessPhone }}</a>
                        @if ($businessEmail)
                            <a href="mailto:{{ $businessEmail }}" class="flex gap-3 break-all transition hover:text-white"><flux:icon.envelope class="mt-0.5 size-4 shrink-0 text-blue-400" />{{ $businessEmail }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-white/8">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-xs text-white/35 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <p>&copy; {{ now()->year }} {{ $businessName }}. All rights reserved.</p>
                    <p>Phones · Accessories · Retail · Wholesale</p>
                </div>
            </div>
        </footer>

        <a href="https://wa.me/{{ $whatsAppPhone }}" target="_blank" rel="noopener noreferrer" class="fixed bottom-5 right-5 z-40 flex size-13 items-center justify-center rounded-full bg-emerald-500 text-white shadow-xl shadow-emerald-500/25 transition hover:-translate-y-1 hover:bg-emerald-600" aria-label="Chat on WhatsApp">
            <flux:icon.chat-bubble-left-right class="size-6" />
        </a>

        @fluxScripts
    </body>
</html>
