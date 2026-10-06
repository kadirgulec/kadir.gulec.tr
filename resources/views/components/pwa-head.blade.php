{{-- Installable app: the manifest (ManifestController); the service worker is registered by resources/js/pwa.js. --}}
{{-- With credentials, so a signed-in note writer gets the "Yeni not" shortcut. --}}
<link rel="manifest" href="{{ route('manifest', absolute: false) }}" crossorigin="use-credentials">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="kg">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
