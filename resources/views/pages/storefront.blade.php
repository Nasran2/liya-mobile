<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::storefront'), Title('Phones & Accessories')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $categoryId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->visibleOnStorefront()
            ->with(['category', 'brand'])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('compatible_models', 'like', "%{$this->search}%")
                        ->orWhereHas('brand', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->categoryId, fn (Builder $query) => $query->where('category_id', $this->categoryId))
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function spotlightProducts()
    {
        return Product::query()
            ->visibleOnStorefront()
            ->with(['category', 'brand'])
            ->latest()
            ->limit(3)
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $query) => $query->visibleOnStorefront())
            ->withCount(['products' => fn (Builder $query) => $query->visibleOnStorefront()])
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function businessName(): string
    {
        return (string) (Setting::get('business_name', 'Liya Mobile') ?: 'Liya Mobile');
    }

    #[Computed]
    public function contactPhone(): string
    {
        return (string) (Setting::get('business_phone', '076 746 4642') ?: '076 746 4642');
    }

    public function inquiryUrl(Product $product): string
    {
        $phone = preg_replace('/\D+/', '', $this->contactPhone);

        if (str_starts_with($phone, '0')) {
            $phone = '94'.substr($phone, 1);
        }

        $message = __('Hi, I would like to know more about :product.', ['product' => $product->name]);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
};
?>

<main>
    <section class="relative isolate overflow-hidden bg-slate-50">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_72%_15%,rgba(59,130,246,0.14),transparent_35%),radial-gradient(circle_at_92%_90%,rgba(14,165,233,0.13),transparent_30%)]"></div>
        <div class="absolute inset-y-0 right-0 -z-10 hidden w-1/2 bg-[linear-gradient(135deg,transparent_10%,rgba(255,255,255,0.75)_100%)] lg:block"></div>

        <div class="mx-auto grid min-h-[640px] max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-24">
            <div class="max-w-xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-blue-700 shadow-sm">
                    <span class="size-2 rounded-full bg-blue-600"></span>
                    New collection available
                </div>
                <h1 class="mt-7 text-balance text-5xl font-black leading-[0.98] tracking-[-0.055em] text-slate-950 sm:text-6xl lg:text-7xl">
                    Smarter tech.
                    <span class="text-blue-600">Better everyday.</span>
                </h1>
                <p class="mt-6 max-w-lg text-pretty text-base leading-7 text-slate-600 sm:text-lg">
                    {{ Setting::get('business_tagline', 'Discover phones, accessories, chargers, audio, and everyday mobile essentials selected for quality and value.') }}
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#products" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-blue-700">
                        Shop new arrivals
                        <flux:icon.arrow-right class="size-4" />
                    </a>
                    <a href="tel:{{ preg_replace('/\s+/', '', $this->contactPhone) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-black text-slate-800 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                        <flux:icon.phone class="size-4" />
                        {{ $this->contactPhone }}
                    </a>
                </div>
                <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-xs font-bold text-slate-500">
                    <span class="inline-flex items-center gap-2"><flux:icon.check-circle class="size-4 text-emerald-500" />Quality checked</span>
                    <span class="inline-flex items-center gap-2"><flux:icon.check-circle class="size-4 text-emerald-500" />Retail & wholesale</span>
                    <span class="inline-flex items-center gap-2"><flux:icon.check-circle class="size-4 text-emerald-500" />Local support</span>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-2xl lg:pl-8" aria-label="Featured products">
                <div class="absolute left-1/2 top-1/2 -z-10 size-[28rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-200/50 blur-3xl"></div>
                <div class="grid grid-cols-2 gap-4 sm:gap-5">
                    @forelse ($this->spotlightProducts as $product)
                        <article wire:key="spotlight-product-{{ $product->id }}" @class([
                            'group relative overflow-hidden rounded-[2rem] border border-white bg-white p-4 shadow-[0_24px_70px_rgba(15,23,42,0.10)] sm:p-5',
                            'row-span-2 mt-10' => $loop->first,
                            'translate-y-0' => ! $loop->first,
                        ])>
                            <div @class([
                                'relative overflow-hidden rounded-[1.4rem] bg-gradient-to-br from-slate-100 to-blue-50',
                                'aspect-[3/4]' => $loop->first,
                                'aspect-[5/3]' => ! $loop->first,
                            ])>
                                @if ($product->image_path)
                                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="size-full object-contain p-5 transition duration-500 group-hover:scale-105" />
                                @else
                                    <div class="flex size-full items-center justify-center">
                                        <div class="absolute size-32 rounded-full bg-blue-200/60 blur-2xl"></div>
                                        @if ($loop->index === 1)
                                            <flux:icon.bolt class="relative size-20 text-blue-500 sm:size-24" />
                                        @elseif ($loop->index === 2)
                                            <flux:icon.speaker-wave class="relative size-20 text-blue-500 sm:size-24" />
                                        @else
                                            <flux:icon.device-phone-mobile class="relative size-24 text-blue-500 sm:size-32" />
                                        @endif
                                    </div>
                                @endif
                                <span class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-blue-700 shadow-sm backdrop-blur">New</span>
                            </div>
                            <div class="px-1 pb-1 pt-4">
                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-blue-600">{{ $product->brand?->name ?? $product->category?->name ?? 'Featured' }}</p>
                                <h2 class="mt-1 line-clamp-2 text-sm font-black leading-snug text-slate-950 sm:text-base">{{ $product->name }}</h2>
                                @if ($product->show_storefront_price)
                                    <p class="mt-2 text-sm font-black text-blue-600">Rs {{ number_format($product->storefront_price ?? $product->selling_price, 2) }}</p>
                                @else
                                    <p class="mt-2 text-sm font-black text-blue-600">Ask for price</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="col-span-2 flex min-h-96 items-center justify-center rounded-[2rem] border border-white bg-white shadow-xl">
                            <flux:icon.device-phone-mobile class="size-32 text-blue-500" />
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-slate-100 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-x divide-y divide-slate-100 px-4 sm:grid-cols-4 sm:divide-y-0 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 px-3 py-5 sm:px-5">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><flux:icon.truck class="size-5" /></span>
                <div><p class="text-xs font-black text-slate-900 sm:text-sm">Fast service</p><p class="mt-0.5 text-[10px] text-slate-400 sm:text-xs">Quick order support</p></div>
            </div>
            <div class="flex items-center gap-3 px-3 py-5 sm:px-5">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><flux:icon.shield-check class="size-5" /></span>
                <div><p class="text-xs font-black text-slate-900 sm:text-sm">Best quality</p><p class="mt-0.5 text-[10px] text-slate-400 sm:text-xs">Selected products</p></div>
            </div>
            <div class="flex items-center gap-3 px-3 py-5 sm:px-5">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><flux:icon.tag class="size-5" /></span>
                <div><p class="text-xs font-black text-slate-900 sm:text-sm">Fair pricing</p><p class="mt-0.5 text-[10px] text-slate-400 sm:text-xs">Clear online prices</p></div>
            </div>
            <div class="flex items-center gap-3 px-3 py-5 sm:px-5">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><flux:icon.chat-bubble-left-right class="size-5" /></span>
                <div><p class="text-xs font-black text-slate-900 sm:text-sm">Helpful support</p><p class="mt-0.5 text-[10px] text-slate-400 sm:text-xs">Ask before you buy</p></div>
            </div>
        </div>
    </section>

    <section id="categories" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-16 sm:px-6 sm:py-22 lg:px-8">
        <div class="flex flex-col gap-3 text-center">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-blue-600">Browse faster</p>
            <h2 class="text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">Shop by category</h2>
            <p class="mx-auto max-w-xl text-sm leading-6 text-slate-500">Jump straight to the accessories and mobile essentials you need.</p>
        </div>

        @if ($this->categories->isNotEmpty())
            <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-4">
                @foreach ($this->categories->take(6) as $category)
                    <button type="button" wire:key="category-card-{{ $category->id }}" wire:click="$set('categoryId', {{ $category->id }})" class="group rounded-2xl border border-slate-200 bg-white p-4 text-center transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl hover:shadow-blue-950/5">
                        <span class="mx-auto flex aspect-square max-w-28 items-center justify-center rounded-full bg-slate-50 text-slate-700 transition group-hover:bg-blue-50 group-hover:text-blue-600">
                            @switch($loop->index % 6)
                                @case(0)<flux:icon.device-phone-mobile class="size-10 sm:size-12" />@break
                                @case(1)<flux:icon.bolt class="size-10 sm:size-12" />@break
                                @case(2)<flux:icon.speaker-wave class="size-10 sm:size-12" />@break
                                @case(3)<flux:icon.computer-desktop class="size-10 sm:size-12" />@break
                                @case(4)<flux:icon.circle-stack class="size-10 sm:size-12" />@break
                                @default<flux:icon.squares-2x2 class="size-10 sm:size-12" />
                            @endswitch
                        </span>
                        <span class="mt-4 block text-sm font-black leading-tight text-slate-900">{{ $category->name }}</span>
                        <span class="mt-1 block text-xs text-slate-400">{{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    <section id="products" class="scroll-mt-24 bg-slate-50 py-16 sm:py-22">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-blue-600">Freshly added</p>
                    <h2 class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">New arrivals</h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">Explore the latest products available from our store.</p>
                </div>
                <label class="group relative block w-full lg:max-w-md">
                    <span class="sr-only">Search products</span>
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-4 top-1/2 z-10 size-5 -translate-y-1/2 text-slate-400 transition group-focus-within:text-blue-600" />
                    <input wire:model.live.debounce.400ms="search" type="search" placeholder="Search products, brands, models..." class="h-13 w-full rounded-xl border border-slate-200 bg-white pl-12 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" />
                </label>
            </div>

            @if ($this->categories->isNotEmpty())
                <div class="no-scrollbar mt-8 flex gap-2 overflow-x-auto pb-2">
                    <button type="button" wire:click="$set('categoryId', null)" @class([
                        'shrink-0 rounded-full border px-4 py-2 text-sm font-bold transition',
                        'border-blue-600 bg-blue-600 text-white' => $categoryId === null,
                        'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:text-blue-600' => $categoryId !== null,
                    ])>All products</button>
                    @foreach ($this->categories as $category)
                        <button type="button" wire:key="product-filter-{{ $category->id }}" wire:click="$set('categoryId', {{ $category->id }})" @class([
                            'shrink-0 rounded-full border px-4 py-2 text-sm font-bold transition',
                            'border-blue-600 bg-blue-600 text-white' => $categoryId === $category->id,
                            'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:text-blue-600' => $categoryId !== $category->id,
                        ])>{{ $category->name }}</button>
                    @endforeach
                </div>
            @endif

            <div wire:loading.class="opacity-50" wire:target="search,categoryId" class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 transition sm:gap-x-5 md:grid-cols-3 lg:grid-cols-4">
                @forelse ($this->products as $product)
                    <article wire:key="storefront-product-{{ $product->id }}" class="group flex min-h-full flex-col">
                        <div class="relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-white transition group-hover:border-blue-200 group-hover:shadow-xl group-hover:shadow-blue-950/5">
                            @if ($product->image_path)
                                <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="size-full object-contain p-5 transition duration-500 group-hover:scale-105" loading="lazy" />
                            @else
                                <div class="flex size-full items-center justify-center bg-gradient-to-br from-white to-blue-50/70">
                                    <div class="absolute size-24 rounded-full bg-blue-100 blur-2xl sm:size-32"></div>
                                    <flux:icon.device-phone-mobile class="relative size-20 text-blue-400 sm:size-28" />
                                </div>
                            @endif
                            <span class="absolute left-3 top-3 rounded-full bg-blue-600 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-white">New</span>
                            <span @class([
                                'absolute bottom-3 right-3 rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider backdrop-blur',
                                'bg-emerald-50/90 text-emerald-700' => $product->stock_quantity > 0,
                                'bg-amber-50/90 text-amber-700' => $product->stock_quantity === 0,
                            ])>{{ $product->stock_quantity > 0 ? 'In stock' : 'Check stock' }}</span>
                        </div>

                        <div class="flex flex-1 flex-col px-1 pt-4">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-blue-600">{{ $product->brand?->name ?? $product->category?->name ?? 'Mobile essential' }}</p>
                            <h3 class="mt-1.5 line-clamp-2 text-sm font-black leading-5 text-slate-900 sm:text-base sm:leading-6">{{ $product->name }}</h3>
                            @if ($product->compatible_models)
                                <p class="mt-1.5 line-clamp-1 text-xs text-slate-400">{{ $product->compatible_models }}</p>
                            @endif
                            <div class="mt-auto pt-4">
                                @if ($product->show_storefront_price)
                                    <p class="text-base font-black text-blue-600 sm:text-lg">Rs {{ number_format($product->storefront_price ?? $product->selling_price, 2) }}</p>
                                @else
                                    <p class="text-base font-black text-blue-600">Ask for price</p>
                                @endif
                                <a href="{{ $this->inquiryUrl($product) }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-blue-600 hover:bg-blue-600 hover:text-white sm:text-sm">
                                    <flux:icon.chat-bubble-left-right class="size-4" />
                                    Ask about it
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><flux:icon.magnifying-glass class="size-6" /></span>
                        <h3 class="mt-5 text-lg font-black text-slate-900">No products found</h3>
                        <p class="mt-2 text-sm text-slate-500">Try another search or category.</p>
                        @if ($search !== '' || $categoryId !== null)
                            <button type="button" wire:click="$set('search', ''); $set('categoryId', null)" class="mt-5 rounded-full border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Clear filters</button>
                        @endif
                    </div>
                @endforelse
            </div>

            @if ($this->products->hasPages())
                <div class="mt-12">{{ $this->products->links() }}</div>
            @endif
        </div>
    </section>

    <section id="why-us" class="scroll-mt-24 px-4 py-16 sm:px-6 sm:py-22 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-blue-600">Buy with confidence</p>
                <h2 class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">Why customers choose us</h2>
                <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-500">Straightforward help, dependable products, and service that continues after you choose.</p>
            </div>
            <div class="mt-10 grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 p-6 sm:p-8">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><flux:icon.sparkles class="size-6" /></span>
                    <h3 class="mt-6 text-xl font-black tracking-tight text-slate-950">Carefully selected</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-500">We focus on useful mobile products and accessories that offer real everyday value.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 p-6 sm:p-8">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><flux:icon.user-group class="size-6" /></span>
                    <h3 class="mt-6 text-xl font-black tracking-tight text-slate-950">Friendly guidance</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-500">Not sure what fits? Ask us before buying and we will help you compare the options.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 p-6 sm:p-8">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><flux:icon.building-storefront class="size-6" /></span>
                    <h3 class="mt-6 text-xl font-black tracking-tight text-slate-950">Retail & wholesale</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-500">Shopping for yourself or stocking your business? Contact us for the right pricing.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="collections" class="bg-slate-50 px-4 py-16 sm:px-6 sm:py-22 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-2">
            <article class="relative min-h-80 overflow-hidden rounded-[2rem] bg-blue-600 p-8 text-white sm:p-10">
                <div class="absolute -bottom-20 -right-10 size-72 rounded-full border-[38px] border-white/10"></div>
                <div class="absolute right-16 top-10 rotate-12 text-white/25"><flux:icon.device-phone-mobile class="size-40" /></div>
                <div class="relative max-w-xs">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-100">Protect your phone</p>
                    <h2 class="mt-4 text-3xl font-black tracking-[-0.04em]">Cases, covers & screen care</h2>
                    <p class="mt-4 text-sm leading-6 text-blue-100">Everyday protection in styles that still feel like you.</p>
                    <a href="#products" class="mt-7 inline-flex rounded-xl bg-white px-5 py-3 text-sm font-black text-blue-700 transition hover:bg-blue-50">Shop collection</a>
                </div>
            </article>
            <article class="relative min-h-80 overflow-hidden rounded-[2rem] bg-slate-950 p-8 text-white sm:p-10">
                <div class="absolute -right-16 -top-24 size-80 rounded-full bg-cyan-400/15 blur-2xl"></div>
                <div class="absolute bottom-5 right-12 -rotate-12 text-cyan-300/30"><flux:icon.bolt class="size-40" /></div>
                <div class="relative max-w-xs">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-300">Power up faster</p>
                    <h2 class="mt-4 text-3xl font-black tracking-[-0.04em]">Chargers, cables & power</h2>
                    <p class="mt-4 text-sm leading-6 text-white/60">Reliable everyday charging for the devices you depend on.</p>
                    <a href="#products" class="mt-7 inline-flex rounded-xl bg-white px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-cyan-50">Explore power</a>
                </div>
            </article>
        </div>
    </section>

    <section class="px-4 py-16 sm:px-6 sm:py-22 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 overflow-hidden rounded-[2rem] bg-gradient-to-r from-blue-600 to-cyan-500 px-7 py-10 text-white shadow-2xl shadow-blue-600/15 sm:px-10 lg:flex-row lg:items-center lg:justify-between lg:px-14 lg:py-12">
            <div class="max-w-2xl">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-blue-100">Need help choosing?</p>
                <h2 class="mt-3 text-3xl font-black tracking-[-0.04em] sm:text-4xl">Talk to someone who knows the products.</h2>
                <p class="mt-4 text-sm leading-6 text-white/75">Call us for availability, product recommendations, retail purchases, or wholesale inquiries.</p>
            </div>
            <a href="tel:{{ preg_replace('/\s+/', '', $this->contactPhone) }}" class="inline-flex shrink-0 items-center justify-center gap-3 rounded-xl bg-white px-6 py-4 text-sm font-black text-blue-700 shadow-xl transition hover:-translate-y-0.5 hover:bg-blue-50">
                <flux:icon.phone class="size-5" />
                Call {{ $this->contactPhone }}
            </a>
        </div>
    </section>
</main>
