<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Theme ZIP & extraction
    |--------------------------------------------------------------------------
    */

    'zip_max_size_bytes' => env('THEME_ZIP_MAX_SIZE_BYTES', 100 * 1024 * 1024), // 100 MB

    /** Allowed file extensions when extracting (no executable/code execution). */
    'allowed_extensions' => [
        'liquid', 'json', 'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'woff', 'woff2', 'ttf', 'eot', 'otf', 'ico', 'mp4', 'webm', 'txt', 'md',
    ],

    /*
    |--------------------------------------------------------------------------
    | .zyg catalog cache (inside extracted theme)
    |--------------------------------------------------------------------------
    */

    'zyg_dir' => '.zyg',
    'zyg_catalog_filename' => 'catalog.json',
    'zyg_catalog_summary_filename' => 'catalog.summary.json',
    'zyg_signature_filename' => 'signature.txt',
    'zyg_catalog_version_filename' => 'catalog_version.txt',
    'zyg_media_manifest_filename' => 'media_manifest.json',
    'zyg_generated_manifest_filename' => 'generated_manifest.json',

    'catalog_version' => '1',

    /*
    |--------------------------------------------------------------------------
    | Paths included in signature (relative to theme root)
    |--------------------------------------------------------------------------
    */

    'signature_globs' => [
        'sections/*.liquid',
        'templates/*.json',
        'config/*.json',
        'locales/*.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | NanoBanana image generation (optional)
    |--------------------------------------------------------------------------
    */

    'nanobanna_base_url' => env('NANOBANNA_BASE_URL', 'https://api.nanobananaapi.ai'),
    'nanobanna_api_key' => env('NANOBANNA_API_KEY'),

];
