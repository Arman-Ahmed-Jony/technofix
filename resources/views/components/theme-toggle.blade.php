{{-- Light / dark switch. Icons swap with CSS (dark: variant), so the right one shows before Alpine loads. --}}
<button type="button" @click="$store.theme.toggle()" {{ $attributes->class('icon-btn') }}
    :aria-label="$store.theme.dark ? 'Switch to light theme' : 'Switch to dark theme'" aria-label="Toggle theme" title="Toggle light / dark theme">
    <span class="relative block size-5">
        <x-icon name="moon" class="absolute inset-0 size-5 transition duration-300 dark:scale-50 dark:-rotate-90 dark:opacity-0" />
        <x-icon name="sun" class="absolute inset-0 size-5 scale-50 rotate-90 opacity-0 transition duration-300 dark:scale-100 dark:rotate-0 dark:opacity-100" />
    </span>
</button>
