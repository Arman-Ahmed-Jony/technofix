<x-mail::message>
# Good news{{ $name ? ', '.\Illuminate\Support\Str::before($name, ' ') : '' }}!

**{{ $product->name }}** is available again — you asked us to let you know.

@if ($product->sale_price)
**Price:** BDT&nbsp;{{ number_format($product->sale_price) }}@if ($product->list_price) <s>BDT&nbsp;{{ number_format($product->list_price) }}</s>@endif

@endif
Stock can go quickly, so order soon if you still need it.

<x-mail::button :url="$product->url">
View the product
</x-mail::button>

Questions? Just reply to this email or call us.
</x-mail::message>
