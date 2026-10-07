{{-- Installable app: the manifest (ManifestController); the service worker is registered by resources/js/pwa.js. --}}
@php
    use App\Listeners\ForgetPushDevice;
    use App\Support\Push\PushNotifier;

    $member = auth()->user();
    $forgetPush = ForgetPushDevice::takeNotice(request());
@endphp
{{-- With credentials, so a signed-in note writer gets the "Yeni not" shortcut. --}}
<link rel="manifest" href="{{ route('manifest', absolute: false) }}" crossorigin="use-credentials">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="kg">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
{{-- Push follows the account (resources/js/pwa.js): who is signed in, and whether they just signed out. --}}
@if ($member?->hasVerifiedEmail() && PushNotifier::publicKey())
    <meta name="kg-push" content="{{ $member->id }}" data-key="{{ PushNotifier::publicKey() }}" data-save="{{ route('push-devices.store', absolute: false) }}" data-token="{{ csrf_token() }}">
@elseif ($forgetPush && ! $member)
    <meta name="kg-push-forget" content="1">
@endif
