@php
    $editing = $address->exists;
    $districts = require database_path('data/bd-districts.php');
    $invalid = fn (string $field) => $errors->has($field) ? 'border-rose-500 ring-4 ring-rose-500/10' : '';
@endphp
<x-account.shell :title="$editing ? 'Edit address' : 'New address'" active="account.addresses"
    :breadcrumbs="[['label' => 'Addresses', 'url' => route('account.addresses')], ['label' => $editing ? 'Edit' : 'New', 'url' => null]]">
    <section class="card max-w-2xl p-6 sm:p-7">
        <h1 class="font-display text-xl font-semibold tracking-tight text-ink">{{ $editing ? 'Edit address' : 'New address' }}</h1>
        <form method="post" action="{{ $editing ? route('account.addresses.update', $address) : route('account.addresses.store') }}" novalidate class="mt-5 space-y-4">
            @csrf
            @if ($editing)
                @method('put')
            @endif
            <x-account.field name="label" label="Label" :value="$address->label" placeholder="e.g. Home, Office" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-account.field name="name" label="Receiver’s name" :value="$address->name" autocomplete="name" :required="true" />
                <x-account.field name="phone" label="Receiver’s mobile" type="tel" inputmode="tel" :value="$address->phone" autocomplete="tel-national" :required="true" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="district" class="mb-1.5 block text-[13px] font-medium text-ink">District <span class="text-accent-600">*</span></label>
                    <select id="district" name="district" class="input cursor-pointer {{ $invalid('district') }}">
                        <option value="">Choose district</option>
                        @foreach ($districts as $division => $list)
                            <optgroup label="{{ $division }}">
                                @foreach ($list as $district)
                                    <option value="{{ $district }}" @selected(old('district', $address->district) === $district)>{{ $district }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <x-form.error for="district" />
                </div>
                <x-account.field name="area" label="Area / thana" :value="$address->area" placeholder="e.g. Mirpur 10" />
            </div>
            <div>
                <label for="address" class="mb-1.5 block text-[13px] font-medium text-ink">Full address <span class="text-accent-600">*</span></label>
                <textarea id="address" name="address" rows="2" placeholder="House, road, block and a nearby landmark" class="input h-auto py-2.5 {{ $invalid('address') }}">{{ old('address', $address->address) }}</textarea>
                <x-form.error for="address" />
            </div>
            <label class="flex cursor-pointer items-center gap-2.5 text-[13.5px] text-ink-2">
                <input type="checkbox" name="is_default" value="1" class="checkbox" @checked(old('is_default', $address->is_default))> Use as my default address
            </label>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn btn-ink">Save address</button>
                <a href="{{ route('account.addresses') }}" class="btn btn-soft">Cancel</a>
            </div>
        </form>
    </section>
</x-account.shell>
