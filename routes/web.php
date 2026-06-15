<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Facades\Statamic\CP\LivePreview;

/*
|--------------------------------------------------------------------------
| Live Preview bridge (addon-owned)
|--------------------------------------------------------------------------
|
| Statamic's native Live Preview iframe loads /mc-live-preview (configured as
| the collection preview target). These routes used to live in each tenant's
| routes/web.php; they now ship with the addon so the behaviour is maintained
| in ONE place and distributed via Composer.
|
| /mc-live-preview        — env-gated bridge:
|     When MC_PREVIEW_FRONTEND_URL is set (e.g. local dev http://localhost:3000,
|     or the production Next.js domain), render the bridge view, which POSTs the
|     unsaved draft to the Next.js draft store and iframes the real React
|     /mc-preview route — a faithful, INTERACTIVE preview. The browser does the
|     POST + iframe load client-side (no server-to-server call).
|     When the env var is NOT set, fall back to the Statamic Antlers render —
|     so the bridge is safe everywhere and only "turns on" where configured.
|
| /mc-live-preview-data   — JSON of the current UNSAVED entry's page_blocks,
|     polled by the bridge on each statamic.preview.updated event.
|
*/

Route::get('/mc-live-preview', function (Request $request) {
    $token = $request->statamicToken();
    $entry = $token ? LivePreview::item($token) : null;

    if (! $entry) {
        return response('Live Preview token missing or expired.', 400);
    }

    $base = rtrim((string) env('MC_PREVIEW_FRONTEND_URL', ''), '/');
    if ($base !== '') {
        return view('mister-chameleon::mc-live-preview', [
            'base'    => $base,
            'path'    => '/mc-preview',
            'payload' => [
                'collection'     => optional($entry->collection())->handle() ?? 'pages',
                'slug'           => $entry->slug(),
                'title'          => $entry->value('title'),
                'seoDescription' => $entry->value('seo_description'),
                'pageBlocks'     => $entry->value('page_blocks') ?? [],
            ],
        ]);
    }

    return $entry->toResponse($request);
});

Route::get('/mc-live-preview-data', function (Request $request) {
    $token = $request->statamicToken();
    $entry = $token ? LivePreview::item($token) : null;

    if (! $entry) {
        return response()->json(['error' => 'Live Preview token missing or expired.'], 400);
    }

    return response()->json([
        'collection'     => optional($entry->collection())->handle() ?? 'pages',
        'slug'           => $entry->slug(),
        'title'          => $entry->value('title'),
        'seoDescription' => $entry->value('seo_description'),
        'pageBlocks'     => $entry->value('page_blocks') ?? [],
    ]);
});
