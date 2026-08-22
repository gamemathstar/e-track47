<?php

/**
 * v2 mobile API configuration. Currently used by the §11.10 system endpoints to
 * surface maintenance / update banners without a DB lookup. Flip `mode` to
 * something other than `normal` (e.g. `maintenance`) to put the app into a
 * banner state, and set the matching copy.
 */
return [
    'system' => [
        'mode' => env('APIV2_SYSTEM_MODE', 'normal'),
        'title' => env('APIV2_SYSTEM_TITLE', ''),
        'body' => env('APIV2_SYSTEM_BODY', ''),
        'eta_label' => env('APIV2_SYSTEM_ETA_LABEL', ''),
        'rotating_status' => [],
    ],
    'update' => [
        'current' => env('APIV2_VERSION_CURRENT', 'v2.0.0'),
        'required' => env('APIV2_VERSION_REQUIRED', 'v2.0.0'),
        'title' => env('APIV2_UPDATE_TITLE', 'You are on the latest version'),
        'body' => env('APIV2_UPDATE_BODY', ''),
        'release_notes_url' => env('APIV2_RELEASE_NOTES_URL', 'https://pdcu.gov.ng/release-notes'),
    ],
];
