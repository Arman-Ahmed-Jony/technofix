{{-- A4 print styles for invoices and packing slips. --}}
    <style>
        @page { size: A4; margin: 14mm 14mm 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 13px/1.45 "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #0f172a; background: #eef2f7; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; gap: 8px; align-items: center; justify-content: center; padding: 10px; background: #0b1b2b; color: #fff; }
        .toolbar a, .toolbar button { font: inherit; font-weight: 600; padding: 7px 14px; border-radius: 8px; border: 1px solid rgb(255 255 255 / .2); background: rgb(255 255 255 / .08); color: #fff; text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: #1d71b8; border-color: #1d71b8; }
        .toolbar .current { background: #fff; color: #0b1b2b; }
        .toolbar span { opacity: .7; margin-left: 8px; }
        .sheet { width: 210mm; min-height: 297mm; margin: 16px auto; padding: 14mm; background: #fff; box-shadow: 0 6px 24px rgb(15 23 42 / .12); }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 14px; border-bottom: 3px solid #1d71b8; }
        .head img { height: 44px; }
        .store { margin-top: 8px; color: #475569; font-size: 12px; }
        .doc { text-align: right; }
        .doc h1 { margin: 0; font-size: 26px; letter-spacing: .06em; text-transform: uppercase; color: #1d71b8; }
        .doc .no { margin-top: 6px; font: 600 15px ui-monospace, Consolas, monospace; }
        .doc .date { color: #475569; font-size: 12px; }
        .parties { display: grid; grid-template-columns: 1.3fr 1fr; gap: 16px; margin: 18px 0; }
        .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
        .box h2 { margin: 0 0 6px; font-size: 10.5px; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
        .box .name { font-size: 15px; font-weight: 700; }
        .box .big { font-size: 14px; }
        .kv { display: grid; grid-template-columns: auto 1fr; gap: 3px 12px; }
        .kv dt { color: #64748b; } .kv dd { margin: 0; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: #475569; background: #f1f5f9; padding: 8px 10px; }
        td { padding: 9px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        td.num, th.num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .muted { color: #64748b; font-size: 11.5px; }
        .check { width: 16px; height: 16px; border: 1.5px solid #334155; border-radius: 3px; display: inline-block; }
        .totals { margin-left: auto; width: 290px; margin-top: 12px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { margin-top: 6px; padding: 10px 12px; border-radius: 10px; background: #0b1b2b; color: #fff; font-size: 15px; font-weight: 700; }
        .notes { margin-top: 18px; padding: 10px 14px; border-left: 3px solid #f39200; background: #fff8ec; border-radius: 6px; }
        .foot { margin-top: 28px; display: flex; justify-content: space-between; gap: 24px; align-items: flex-end; }
        .sign { width: 180px; border-top: 1px solid #94a3b8; padding-top: 6px; text-align: center; color: #475569; font-size: 11.5px; }
        .thanks { color: #475569; font-size: 11.5px; max-width: 360px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; page-break-after: always; }
            .sheet:last-child { page-break-after: auto; }
            .totals .grand { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
