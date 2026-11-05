@if ($academy?->letterhead_path)
    <style>
        .academy-letterhead-print-background { display: none; }

        @media print {
            @page { margin: 0; }
            body { background: transparent !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body > :not(.academy-letterhead-print-background) { position: relative; z-index: 1; }
            .academy-letterhead-print-background { display: block !important; position: fixed !important; inset: 0 !important; z-index: 0 !important; width: 100vw; height: 100vh; pointer-events: none; }
            .academy-letterhead-print-background img { display: block; width: 100%; height: 100%; object-fit: fill; opacity: .12; }
            .print-document, .challan, .receipt { background: transparent !important; }
            .challan-letterhead, .receipt-letterhead { display: none !important; }
        }
    </style>
    <div class="academy-letterhead-print-background" aria-hidden="true">
        <img src="{{ asset('storage/'.$academy->letterhead_path) }}" alt="">
    </div>
@endif
