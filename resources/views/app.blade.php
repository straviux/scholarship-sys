<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- DNS prefetch / preconnect for external resources -->
    <link rel="preconnect" href="//fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    @php
    // Read Vite manifest to emit modulepreload hints for vendor chunks.
    // This breaks the critical request chain: vendor chunks start downloading
    // alongside app.js instead of waiting for app.js to be parsed first.
    $manifestPath = public_path('build/.vite/manifest.json');
    if (!file_exists($manifestPath)) {
    $manifestPath = public_path('build/manifest.json');
    }
    $viteChunks = file_exists($manifestPath)
    ? json_decode(file_get_contents($manifestPath), true)
    : [];
    @endphp

    {{-- Preload vendor chunks so browser fetches them in parallel with app.js --}}
    @foreach($viteChunks as $key => $chunk)
    @if(isset($chunk['file']) && empty($chunk['isEntry']) && str_contains($chunk['file'], 'vendor'))
    @if(str_ends_with($chunk['file'], '.js'))
    <link rel="modulepreload" href="/build/{{ $chunk['file'] }}">
    @elseif(str_ends_with($chunk['file'], '.css'))
    <link rel="preload" href="/build/{{ $chunk['file'] }}" as="style">
    @endif
    @endif
    {{-- Preload any CSS associated with entry chunks --}}
    @if(!empty($chunk['isEntry']) && !empty($chunk['css']))
    @foreach($chunk['css'] as $cssFile)
    <link rel="preload" href="/build/{{ $cssFile }}" as="style">
    @endforeach
    @endif
    @endforeach

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead

    {{-- Deferred non-critical CSS (icons, datepicker, toastify).
         media="print" causes immediate async download without blocking render.
         onload switches it to all media once downloaded. --}}
    @php
    $deferredCss = null;
    foreach ($viteChunks as $chunk) {
    if (isset($chunk['src']) && str_contains($chunk['src'], 'deferred.css') && isset($chunk['file'])) {
    $deferredCss = $chunk['file'];
    break;
    }
    }
    @endphp
    @if($deferredCss)
    <link rel="stylesheet" href="/build/{{ $deferredCss }}"
        media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="/build/{{ $deferredCss }}">
    </noscript>
    @endif
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>