<x-mail::message>
# Hi {{ \Illuminate\Support\Str::before($order->name, ' ') }},

Thanks again for your order **{{ $order->number }}**. We hope everything is working well!

Would you take a minute to review what you bought? It helps other customers choose — and helps us improve.

@foreach ($products as $product)
<x-mail::button :url="$product->url.'?write=review#reviews'">
Review: {{ \Illuminate\Support\Str::limit($product->name, 48) }}
</x-mail::button>
@endforeach

Something not right? Reply to this email or call us — we’ll sort it out.
</x-mail::message>
