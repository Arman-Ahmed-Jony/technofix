@props(['for'])
@error($for)
    <p {{ $attributes->class('mt-1.5 flex items-center gap-1.5 text-[12.5px] font-medium text-rose-600 dark:text-rose-400') }} role="alert">
        <x-icon name="alert-circle" class="size-3.5" /> {{ $message }}
    </p>
@enderror
