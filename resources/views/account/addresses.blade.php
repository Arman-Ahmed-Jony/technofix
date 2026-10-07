<x-account.shell title="Addresses" active="account.addresses" :breadcrumbs="[['label' => 'Addresses', 'url' => null]]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold tracking-tight text-ink">Addresses</h1>
            <p class="mt-1 text-sm text-muted">Choose one at checkout instead of typing it again.</p>
        </div>
        @if ($addresses->count() < \App\Models\CustomerAddress::MAX_PER_CUSTOMER)
            <a href="{{ route('account.addresses.create') }}" class="btn btn-sm btn-ink"><x-icon name="plus" class="size-4" /> Add address</a>
        @endif
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        @forelse ($addresses as $address)
            <article @class(['card flex flex-col p-5', 'ring-2 ring-brand-500/40' => $address->is_default])>
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-ink">{{ $address->label ?: 'Address' }}</span>
                    @if ($address->is_default)
                        <span class="chip bg-brand-500/10 py-0.5 text-[11px] text-highlight ring-1 ring-brand-500/25">Default</span>
                    @endif
                </div>
                <p class="mt-2 text-[14px] text-ink">{{ $address->name }} <span class="font-mono text-[12.5px] text-muted">{{ $address->phone }}</span></p>
                <p class="mt-1 flex-1 text-[13.5px] text-muted">{{ $address->summary() }}</p>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-3">
                    <a href="{{ route('account.addresses.edit', $address) }}" class="btn btn-sm btn-soft"><x-icon name="pencil" class="size-4" /> Edit</a>
                    @unless ($address->is_default)
                        <form method="post" action="{{ route('account.addresses.default', $address) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-soft">Make default</button>
                        </form>
                    @endunless
                    <form method="post" action="{{ route('account.addresses.destroy', $address) }}" x-data @submit="if (! confirm('Delete this address?')) $event.preventDefault()" class="ml-auto">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-sm btn-soft hover:text-rose-600" aria-label="Delete address"><x-icon name="trash" class="size-4" /></button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card col-span-full px-6 py-12 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon name="map-pin" class="size-6 [stroke:url(#tf-grad)]" /></span>
                <p class="mt-4 font-semibold text-ink">No saved addresses yet</p>
                <p class="mt-1 text-sm text-muted">Addresses you use at checkout can be saved too.</p>
            </div>
        @endforelse
    </div>
</x-account.shell>
