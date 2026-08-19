<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<title>{{ $title }}</title>
<style>
    body, table, td { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    body { margin: 0; padding: 0; background-color: #f4f4f5; -webkit-text-size-adjust: none; }
    a { color: {{ $accentDark }}; }
    .header .brand { color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; margin: 0; }
    .header .tagline { color: rgba(255,255,255,0.85); font-size: 12px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; margin: 4px 0 0 0; }
    .badge { display: inline-block; padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: 0.02em; background-color: {{ $badgeBg }}; color: {{ $badgeText }}; margin-bottom: 18px; }
    h1 { font-size: 20px; font-weight: 700; color: #18181b; margin: 0 0 12px 0; }
    p.intro { font-size: 15px; line-height: 1.6; color: #52525b; margin: 0; }
    .card-label { font-size: 11px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; padding-bottom: 3px; }
    .card-value { font-size: 15px; font-weight: 600; color: #18181b; }
    .cta { display: inline-block; background-color: {{ $accent }}; color: #ffffff !important; font-size: 14px; font-weight: 700; text-decoration: none; padding: 13px 28px; border-radius: 8px; }
    .footer p { font-size: 12px; color: #a1a1aa; margin: 2px 0; }
</style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f4f4f5;">
<tr>
<td align="center" style="padding: 32px 16px;">

<table width="560" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 560px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">

<tr>
<td class="header" style="background: linear-gradient(135deg, {{ $accent }} 0%, {{ $accentDark }} 100%); padding: 28px 32px; text-align: center;">
<p class="brand">CvTech</p>
<p class="tagline">{{ $tagline ?? __('Διαχείριση Αδειών') }}</p>
</td>
</tr>

<tr>
<td style="padding: 36px 32px 28px 32px;">
@if($badgeLabel)
<span class="badge">{{ $badgeLabel }}</span><br>
@endif
<h1>{{ $heading }}</h1>
<p class="intro">{!! $intro !!}</p>
</td>
</tr>

<tr>
<td style="padding: 0 32px 28px 32px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #fafafa; border-radius: 10px;">
@foreach($details as $label => $value)
<tr>
<td style="padding: 14px 20px; {{ ! $loop->last ? 'border-bottom: 1px solid #e4e4e7;' : '' }}">
<div class="card-label">{{ $label }}</div>
<div class="card-value">{{ $value }}</div>
</td>
</tr>
@endforeach
</table>
</td>
</tr>

@if($ctaUrl)
<tr>
<td align="center" style="padding: 0 32px 36px 32px;">
<a href="{{ $ctaUrl }}" class="cta">{{ $ctaLabel }}</a>
</td>
</tr>
@endif

<tr>
<td class="footer" align="center" style="padding: 24px 32px; border-top: 1px solid #f0f0f1;">
<p><strong>CvTech</strong> — {{ $footerNote ?? __('Σύστημα διαχείρισης αδειών προσωπικού') }}</p>
<p>{{ config('app.name') }}</p>
</td>
</tr>

</table>

</td>
</tr>
</table>
</body>
</html>
