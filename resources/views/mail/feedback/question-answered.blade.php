<x-mail::message>
# Hi {{ \Illuminate\Support\Str::before($question->customer?->name ?? 'there', ' ') }},

You asked about **{{ $question->product->name }}**:

> {{ $question->question }}

**Our answer:**

{{ $question->answer }}

<x-mail::button :url="$question->product->url.'#questions'">
View the product
</x-mail::button>

More questions? Just reply to this email or call us.
</x-mail::message>
