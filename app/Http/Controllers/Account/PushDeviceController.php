<?php

namespace App\Http\Controllers\Account;

use App\Actions\Push\SavePushDevice;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The installable app sends this device's push subscription here on its own
 * (resources/js/pwa.js): after signing in again on a device where the member
 * had push on, so it does not have to be switched on again.
 */
class PushDeviceController extends Controller
{
    public function __invoke(Request $request, SavePushDevice $savePushDevice): Response
    {
        $savePushDevice->handle($request->user(), $request->all(), $request->userAgent());

        return response()->noContent();
    }
}
