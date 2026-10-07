@props(['product', 'iconClass' => 'size-[44%]'])
{{-- Product photo on a white well, or — until real photos exist — a lit "render" of the category icon. --}}
@if ($product->image)
    <div {{ $attributes->class('relative flex aspect-square items-center justify-center overflow-hidden rounded-xl bg-white') }}>
        <img src="{{ $product->image }}" alt="{{ $product->name }}" loading="lazy" decoding="async" width="400" height="400" class="size-full object-contain p-4">
    </div>
@else
    <div {{ $attributes->class('relative flex aspect-square items-center justify-center overflow-hidden rounded-xl bg-card-2') }}>
        <div class="absolute inset-0 bg-[radial-gradient(75%_55%_at_50%_0%,rgb(0_159_227/0.14),transparent_70%)] dark:bg-[radial-gradient(75%_60%_at_50%_0%,rgb(38_171_230/0.22),transparent_70%)]"></div>
        <div class="absolute inset-x-[27%] bottom-[17%] h-[7%] rounded-[100%] bg-navy-900/15 blur-md dark:bg-black/60"></div>
        <x-icon :name="$product->icon" class="relative {{ $iconClass }} [stroke:url(#tf-grad)] [stroke-width:1.15] drop-shadow-[0_10px_14px_rgb(3_56_81/0.18)] dark:drop-shadow-[0_10px_18px_rgb(0_159_227/0.25)]" />
    </div>
@endif
