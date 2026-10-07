@props(['product', 'size' => 'md'])
{{-- Price with a smaller ৳ sign; the "was" price sits above it (struck through) when discounted — campaign deals included. --}}
@php($big = $size === 'lg')
<div {{ $attributes }}>
    @if ($product->sale_price === null)
        <span @class(['font-semibold text-ink', 'font-display text-2xl tracking-tight' => $big, 'text-[14px]' => ! $big])>Call for price</span>
    @else
        @if ($product->discount)
            <span @class(['block text-faint line-through tabular-nums', 'text-[15px]' => $big, 'text-[11.5px] leading-4' => ! $big])>৳{{ number_format($product->list_price) }}</span>
        @endif
        <span @class(['price block', 'font-display text-[34px] leading-tight font-semibold' => $big, 'text-[17px] leading-6' => ! $big])>
            <span class="mr-0.5 text-[0.72em] font-medium text-muted">৳</span>{{ number_format($product->sale_price) }}
        </span>
    @endif
</div>
