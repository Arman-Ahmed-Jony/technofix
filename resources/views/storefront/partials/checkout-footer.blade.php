{{-- Slim footer for cart / checkout. --}}
<footer class="mt-16 border-t border-line">
    <div class="container-x flex flex-col items-center justify-between gap-3 py-6 text-xs text-muted sm:flex-row max-lg:pb-28">
        <p>© {{ date('Y') }} TechnoFix. All rights reserved.</p>
        <nav class="flex flex-wrap items-center gap-x-5 gap-y-1" aria-label="Policies">
            <a href="{{ url('page/delivery') }}" class="hover:text-ink">Delivery</a>
            <a href="{{ url('page/refund-policy') }}" class="hover:text-ink">Return &amp; refund</a>
            <a href="{{ url('page/warranty') }}" class="hover:text-ink">Warranty</a>
            <a href="{{ url('page/privacy-policy') }}" class="hover:text-ink">Privacy</a>
            <a href="{{ url('page/terms') }}" class="hover:text-ink">Terms</a>
        </nav>
    </div>
</footer>
