{{-- Overlapping thumbnails of an order's items (photo, or the category icon). Expects $items (OrderItem collection). --}}
@php
    $size ??= 'size-10';
    $shown = $items->take(3);
@endphp
<span class="flex shrink-0 items-center -space-x-2.5">
    @foreach ($shown as $item)
        @php $product = $item->product; @endphp
        <span @class(['relative grid place-items-center overflow-hidden rounded-xl ring-2 ring-card', $size, 'bg-white' => $product?->image, 'bg-card-2' => ! $product?->image]) title="{{ $item->label() }}">
            @if ($product?->image)
                <img src="{{ $product->image }}" alt="" class="size-full object-contain p-1" loading="lazy">
            @else
                <x-icon :name="$product?->icon ?? 'package'" class="size-[55%] [stroke:url(#tf-grad)] [stroke-width:1.5]" />
            @endif
        </span>
    @endforeach
    @if ($items->count() > $shown->count())
        <span @class(['relative grid place-items-center rounded-xl bg-card-2 font-mono text-[11px] font-medium text-muted ring-2 ring-card', $size])>+{{ $items->count() - $shown->count() }}</span>
    @endif
</span>
