<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#252440">
<link rel="apple-touch-icon" href="{{ asset('logos/apple-touch-icon.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Plen@">
<meta name="mobile-web-app-capable" content="yes">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }
</script>
