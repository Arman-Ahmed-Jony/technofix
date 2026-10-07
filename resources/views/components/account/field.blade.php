@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'bag' => 'default'])
@php
    $error = $errors->getBag($bag)->first($name);
    $isPassword = $type === 'password';
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1.5 block text-[13px] font-medium text-ink">{{ $label }} @if ($required)<span class="text-accent-600">*</span>@endif</label>
    <div @class(['relative' => $isPassword]) @if ($isPassword) x-data="{ show: false }" @endif>
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $isPassword ? '' : old($name, $value) }}" @required($required)
            @if ($isPassword) :type="show ? 'text' : 'password'" @endif
            {{ $attributes->except('class')->class(['input', 'pr-12' => $isPassword, 'border-rose-500 ring-4 ring-rose-500/10' => $error]) }}>
        @if ($isPassword)
            {{-- Show / hide the password (keeps focus in the field; not a tab stop between fields). --}}
            <button type="button" @click="show = ! show" @mousedown.prevent
                class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-xl text-faint transition hover:text-ink focus-visible:text-ink"
                :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show.toString()" aria-controls="{{ $name }}">
                <x-icon name="eye" class="size-[18px]" x-show="! show" />
                <x-icon name="eye-off" class="size-[18px]" x-show="show" x-cloak />
            </button>
        @endif
    </div>
    @if ($error)
        <p class="mt-1.5 flex items-center gap-1.5 text-[12.5px] font-medium text-rose-600 dark:text-rose-400" role="alert"><x-icon name="alert-circle" class="size-3.5" /> {{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-[12px] text-faint">{{ $hint }}</p>
    @endif
</div>
