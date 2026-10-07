@php
    // Rendered by dompdf (PcBuilderController::quote): tables only — no flexbox/grid. DejaVu Sans covers — · × ’.
    $money = fn (?int $amount) => number_format((int) $amount);
    $errors = collect($issues)->where('level', 'error');
    $logo = \App\Support\Brand::emailLogoData();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PC build quotation {{ $number }} — TechnoFix</title>
    <style>
        @page { margin: 34px 38px 44px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { margin: 0; font-size: 9.5px; line-height: 1.45; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .head .doc { text-align: right; }
        .logo { height: 34px; }
        .store { margin-top: 6px; color: #475569; font-size: 8.5px; }
        h1 { margin: 0; font-size: 20px; letter-spacing: 2px; color: #1d71b8; }
        .no { margin-top: 4px; font-size: 11px; font-weight: bold; }
        .muted { color: #64748b; }
        .rule { height: 3px; background: #1d71b8; margin: 12px 0 14px; }
        .boxes td.box { width: 50%; vertical-align: top; border: 1px solid #e2e8f0; padding: 9px 11px; }
        .boxes td.gap { width: 12px; border: 0; }
        .label { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 4px; }
        .big { font-size: 12px; font-weight: bold; }
        .link { color: #1d71b8; font-size: 8px; }
        .kv td { padding: 1px 0; vertical-align: top; }
        .kv td.k { color: #64748b; width: 88px; }
        .kv td.v { font-weight: bold; }
        .parts { margin-top: 14px; }
        .parts th { background: #f1f5f9; color: #475569; font-size: 7.5px; letter-spacing: 1px; text-transform: uppercase; text-align: left; padding: 6px 7px; }
        .parts td { padding: 7px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .parts .num { text-align: right; white-space: nowrap; }
        .parts .qty { text-align: center; white-space: nowrap; }
        .slot { font-size: 7.5px; letter-spacing: 1px; text-transform: uppercase; color: #1d71b8; font-weight: bold; }
        .name { font-size: 10px; }
        .parts td.fill { border-bottom: 0; padding: 10px 0 0; }
        .parts td.grand { border-bottom: 0; background: #0b1b2b; color: #ffffff; font-size: 12px; font-weight: bold; padding: 9px 7px 9px 11px; }
        .issues { margin-top: 14px; padding: 8px 11px; border-left: 3px solid #e11d48; background: #fff1f2; }
        .good { margin-top: 14px; padding: 8px 11px; border-left: 3px solid #059669; background: #ecfdf5; }
        .note { margin-top: 10px; padding: 8px 11px; border-left: 3px solid #f39200; background: #fff8ec; }
        .foot { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 8px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="foot">TechnoFix · Hotline {{ $store->hotline }} · {{ $store->email }} · Delivery charges are added at checkout.</div>

    <table class="head">
        <tr>
            <td>
                <img src="{{ $logo }}" class="logo" alt="TechnoFix">
                <div class="store">{{ $store->address }}<br>Hotline {{ $store->hotline }} · {{ $store->email }}</div>
            </td>
            <td class="doc">
                <h1>QUOTATION</h1>
                <div class="no">{{ $number }}</div>
                <div class="muted">{{ $date->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <table class="boxes">
        <tr>
            <td class="box">
                <div class="label">PC build</div>
                <div class="big">{{ collect($build->items)->sum('qty') }} parts · BDT {{ $money($build->subtotal()) }}</div>
                <div class="muted" style="margin-top:4px">Open or change this build online:</div>
                <div class="link">{{ $link }}</div>
            </td>
            <td class="gap"></td>
            <td class="box">
                <div class="label">Summary</div>
                <table class="kv">
                    <tr><td class="k">Compatibility</td><td class="v">{{ $errors->isEmpty() ? 'No problems found' : $errors->count().' problem(s) — see below' }}</td></tr>
                    <tr><td class="k">Estimated power</td><td class="v">{{ $power['parts'] ? $power['estimate'].' W (recommended PSU '.$power['recommended'].' W)' : '—' }}</td></tr>
                    @if ($build->missing()->isNotEmpty())
                        <tr><td class="k">Still needed</td><td class="v">{{ $build->missing()->pluck('name')->join(', ') }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="parts">
        <thead>
            <tr><th style="width:18px">#</th><th>Part</th><th class="qty" style="width:34px">Qty</th><th class="num" style="width:96px">Unit price (BDT)</th><th class="num" style="width:86px">Total (BDT)</th></tr>
        </thead>
        <tbody>
            @foreach ($build->items as $slotId => $item)
                @php $product = $item['product']; @endphp
                <tr>
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td>
                        <div class="slot">{{ $build->slots[$slotId]->name }}</div>
                        <div class="name">{{ $product->name }}</div>
                        <div class="muted">{{ collect([$product->sku, \App\Support\Warranty::label($product->warranty), $product->stock->label()])->filter()->join(' · ') }}</div>
                    </td>
                    <td class="qty">{{ $item['qty'] }}</td>
                    <td class="num">{{ $money($product->sale_price) }}</td>
                    <td class="num">{{ $money((int) $product->sale_price * $item['qty']) }}</td>
                </tr>
            @endforeach
        </tbody>
        {{-- Total box lines up with the Qty → Total columns above. --}}
        <tr><td class="fill" colspan="5"></td></tr>
        <tr><td class="fill" colspan="2"></td><td class="grand" colspan="3">Total <span style="float:right">BDT {{ $money($build->subtotal()) }}</span></td></tr>
    </table>

    @if ($issues)
        <div class="issues">
            @foreach ($issues as $issue)
                <div>{{ $issue['level'] === 'error' ? 'Problem' : 'Note' }}: {{ $issue['message'] }}</div>
            @endforeach
        </div>
    @else
        <div class="good">Compatibility checked: no problems found between the chosen parts.</div>
    @endif

    @if (filled($settings->quote_note))
        <div class="note">{{ $settings->quote_note }}</div>
    @endif
</body>
</html>
