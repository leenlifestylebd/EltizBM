/* ── Reset ───────────────────────────────────── */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; background: #e5e7eb; }

/* ── Preview page ────────────────────────────── */
.preview-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    padding: 24px 16px;
    gap: 20px;
}
.action-bar {
    display: flex;
    gap: 10px;
    align-items: center;
    background: #fff;
    border-radius: 12px;
    padding: 10px 18px;
    box-shadow: 0 1px 6px rgba(0,0,0,.10);
}
.btn-print {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; background: #111; color: #fff;
    border: none; border-radius: 8px; cursor: pointer;
    font-size: 13px; font-weight: 600; font-family: inherit;
}
.btn-back {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; background: #f3f4f6; color: #374151;
    border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 13px; text-decoration: none; font-weight: 500;
}
.label-info { font-size: 12px; color: #6b7280; }

/* ── Cut guide (screen only) ─────────────────── */
.cut-wrap {
    position: relative;
    padding: 10px;
    border: 1.5px dashed #bbb;
    display: inline-block;
    background: #fff;
    border-radius: 2px;
}
.cut-wrap::before {
    content: "✂";
    position: absolute;
    top: -2px; left: -2px;
    font-size: 12px; color: #aaa;
    transform: rotate(-45deg);
    background: #e5e7eb;
    line-height: 1;
}

/* ══════════════════════════════════════════════
   LABEL  —  2 × 3 inch
   Usable print area: 1.8in × 3in (0.1in side margin)
   ══════════════════════════════════════════════ */
.label {
    width: 2in;
    height: 3in;
    border: 1.5px solid #222;
    display: flex;
    flex-direction: column;
    background: #fff;
    overflow: hidden;
    font-family: Arial, Helvetica, sans-serif;
}

/* ── Divider shared ──────────────────────────── */
.bd { border-bottom: 1px solid #333; }
.bd-thick { border-bottom: 1.5px solid #000; }

/* ════════════════════════════════════════════
   ① HEADER  (logo + biz name / merchant id)
   ════════════════════════════════════════════ */
.sec-header {
    flex-shrink: 0;
    padding: 3px 7px 4px;
    border-bottom: 1.5px solid #000;
}
.header-logo-row {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 2px;
}
.biz-logo {
    height: 20px;
    max-width: 120px;
    object-fit: contain;
    display: block;
}
.header-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
}
.biz-name {
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .4px;
    line-height: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
}
.merchant-block { text-align: right; flex-shrink: 0; }
.merchant-lbl {
    display: block;
    font-size: 6px;
    font-weight: 700;
    text-transform: uppercase;
    color: #888;
    letter-spacing: .5px;
    line-height: 1.2;
}
.merchant-id {
    font-size: 9px;
    font-weight: 900;
    font-family: 'Courier New', monospace;
    letter-spacing: .8px;
    color: #000;
    line-height: 1;
}

/* ════════════════════════════════════════════
   ② BARCODE
   ════════════════════════════════════════════ */
.sec-barcode {
    flex-shrink: 0;
    padding: 3px 5px 2px;
    border-bottom: 1px solid #333;
    text-align: center;
}
.barcode-svg {
    display: block;
    width: 100%;
    height: 38px;
}
.barcode-num {
    font-family: 'Courier New', monospace;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 1.5px;
    color: #000;
    margin-top: 1px;
    line-height: 1;
}

/* ════════════════════════════════════════════
   ③ CONSIGNMENT ID
   ════════════════════════════════════════════ */
.sec-cid {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 3px 7px;
    border-bottom: 1px solid #333;
}
.cid-key {
    font-size: 7px;
    font-weight: 700;
    text-transform: uppercase;
    color: #666;
    letter-spacing: .5px;
    flex-shrink: 0;
}
.cid-val {
    font-size: 10.5px;
    font-weight: 900;
    font-family: 'Courier New', monospace;
    letter-spacing: .6px;
    color: #000;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ════════════════════════════════════════════
   ④ CUSTOMER INFO
   ════════════════════════════════════════════ */
.sec-customer {
    flex-shrink: 0;
    padding: 3px 7px 4px;
    border-bottom: 1.5px solid #000;
}
.row {
    display: flex;
    align-items: flex-start;
    gap: 4px;
    margin-bottom: 1.5px;
}
.row:last-child { margin-bottom: 0; }
.row-key {
    font-size: 6.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: #777;
    letter-spacing: .3px;
    flex-shrink: 0;
    width: 40px;
    padding-top: 1.5px;
    line-height: 1;
}
.row-val {
    font-size: 8.5px;
    color: #111;
    line-height: 1.3;
    flex: 1;
    word-break: break-word;
    overflow-wrap: break-word;
}
/* Name & phone bigger */
.val-name  { font-size: 11px; font-weight: 900; text-transform: uppercase; line-height: 1.1; }
.val-phone { font-size: 10.5px; font-weight: 800; letter-spacing: .4px; }

/* ════════════════════════════════════════════
   ⑤ ITEMS  (flex:1 — fills remaining height)
   ════════════════════════════════════════════ */
.sec-items {
    flex: 1;
    padding: 3px 7px;
    border-bottom: 1.5px solid #000;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.item-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 3px;
    margin-bottom: 2px;
}
.item-row:last-child { margin-bottom: 0; }
.item-name {
    font-size: 8.5px;
    font-weight: 700;
    color: #111;
    flex: 1;
    min-width: 0;
    white-space: normal;
    line-height: 1.15;
    word-break: break-word;
}
.item-detail {
    font-size: 8px;
    font-weight: 700;
    color: #444;
    white-space: nowrap;
    flex-shrink: 0;
}
.item-more {
    font-size: 7.5px;
    color: #999;
    font-style: italic;
    margin-top: 1px;
}

/* ════════════════════════════════════════════
   ⑥ COD BAND
   ════════════════════════════════════════════ */
.sec-cod {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px 9px;
    background: #111;
    color: #fff;
}
.cod-lbl {
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #bbb;
    line-height: 1;
}
.cod-amt {
    font-size: 18px;
    font-weight: 900;
    color: #fff;
    letter-spacing: -.5px;
    line-height: 1;
}

/* ════════════════════════════════════════════
   ⑦ FOOTER
   ════════════════════════════════════════════ */
.sec-footer {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2px 7px;
    background: #fff;
}
.footer-date  { font-size: 7px; color: #aaa; }
.footer-brand { font-size: 8px; font-weight: 800; color: #333; letter-spacing: .3px; }

/* ════════════════════════════════════════════
   PRINT
   ════════════════════════════════════════════ */
/* Follow the printer's own page setup / selected paper size instead of forcing
   a fixed 2x3in. The label then fills whatever page size is chosen. */
@page {
    size: auto;
    margin: 0;
}
@media print {
    html, body { background: #fff !important; margin: 0; }
    .no-print   { display: none !important; }
    .preview-wrap { display: block; padding: 0; min-height: unset; }
    .cut-wrap   { display: block; padding: 0; border: none; }
    .cut-wrap::before { display: none; }
    .label {
        width: 100%;
        height: 100vh;
        border: none;
        page-break-inside: avoid;
        overflow: hidden;
    }
}
