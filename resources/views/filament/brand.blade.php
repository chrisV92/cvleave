{{-- Brand lockup for the Filament panel headers: the mark plus the wordmark,
     rendered inline so it picks up the panel's own font instead of shipping
     text baked into an SVG. --}}
<span style="display:inline-flex;align-items:center;gap:9px;">
    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
         style="width:30px;height:30px;display:block;flex:none;">
        <defs>
            <linearGradient id="cvtechBrandGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#6366f1"/>
                <stop offset="1" stop-color="#4338ca"/>
            </linearGradient>
        </defs>
        <rect width="64" height="64" rx="15" fill="url(#cvtechBrandGrad)"/>
        <path d="M18 23 L32 45 L46 23" fill="none" stroke="#fff" stroke-width="7"
              stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="50" cy="16" r="4.5" fill="#fbbf24"/>
    </svg>
    <span style="font-size:17px;font-weight:800;letter-spacing:-0.03em;line-height:1;"
          class="text-gray-950 dark:text-white">Cv<span style="color:#6366f1;">Tech</span></span>
</span>
