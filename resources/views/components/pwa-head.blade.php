{{-- Installable app: the manifest (ManifestController); the service worker is registered by resources/js/pwa.js. --}}
<link rel="manifest" href="{{ route('manifest', absolute: false) }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="kg">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
