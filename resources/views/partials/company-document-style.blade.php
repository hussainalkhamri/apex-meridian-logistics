<style>
    .company-letterhead { display:flex; align-items:center; justify-content:space-between; gap:24px; padding:16px 0; border-top:4px solid #ef2330; border-bottom:1px solid #dbe1ef; color:#26376b; }
    .company-letterhead-logo { width:210px; max-width:42%; height:auto; object-fit:contain; flex-shrink:0; }
    .company-letterhead-details { max-width:55%; font-size:9px; line-height:1.7; text-align:end; }
    .company-letterhead-details strong { font-size:12px; }
    .company-document-footer { margin-top:24px; break-inside:avoid; page-break-inside:avoid; }
    .company-document-partners { display:flex; align-items:center; justify-content:center; gap:22px; padding:14px 0; border-top:1px solid #dbe1ef; background:#fff; color:#64748b; font-size:9px; }
    .company-document-partners img { width:110px; height:42px; object-fit:contain; }
    .company-document-contacts { background:#26376b; border-top:3px solid #ef2330; color:#fff; text-align:center; padding:10px 12px; font-size:9px; line-height:1.8; overflow-wrap:anywhere; }
    .company-document-contacts a { color:inherit; text-decoration:none; display:inline-block; margin:0 5px; }
    @media (max-width:520px) {
        .company-letterhead { gap:12px; }
        .company-letterhead-details strong { font-size:10px; }
        .company-document-partners { gap:8px; flex-wrap:wrap; }
        .company-document-partners img { width:85px; }
    }
    @media print {
        .company-letterhead { padding:8px 0; }
        .company-letterhead-logo { width:190px; }
        .company-letterhead-details { font-size:8px; }
        .company-letterhead-details strong { font-size:10px; }
        .company-document-footer { margin-top:12px; }
        .company-document-partners { padding:7px 0; gap:18px; }
        .company-document-partners img { width:95px; height:30px; }
        .company-document-contacts { padding:6px 8px; font-size:8px; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
        .print-single-page { overflow:visible!important; page-break-inside:auto!important; break-inside:auto!important; }
        tr { break-inside:avoid; page-break-inside:avoid; }
        thead { display:table-header-group; }
        .invoice-spacer { display:none; }
        .quote-paper .mt-6 { margin-top:12px; }
        .quote-paper .my-6 { margin-top:12px; margin-bottom:12px; }
        .quote-paper .p-4, .quote-paper .p-5 { padding:10px; }
        .quote-paper th, .quote-paper td { padding-top:5px; padding-bottom:5px; }
        .quote-paper footer .h-20 { height:35px; }
        .quote-paper footer.pt-6 { padding-top:10px; }
        .quote-paper .company-letterhead { padding:4px 0; }
        .quote-paper .company-document-footer { margin-top:6px; }
        .quote-paper .company-document-partners { padding:4px 0; }
    }
</style>
