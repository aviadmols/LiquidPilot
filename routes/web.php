<?php

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['web', 'auth'])->prefix('admin')->group(function (): void {
    Route::get('/download-run-media/{agentRun}/{mediaAsset}', function (int $agentRun, int $mediaAsset) {
        $asset = MediaAsset::where('id', $mediaAsset)->where('agent_run_id', $agentRun)->firstOrFail();
        $run = $asset->agentRun;
        $run->load('themeRevision');
        $base = $run->themeRevision?->extracted_path;
        if (!$base || !$asset->rel_path) {
            abort(404);
        }
        $fullPath = $base . \DIRECTORY_SEPARATOR . str_replace('/', \DIRECTORY_SEPARATOR, $asset->rel_path);
        if (!is_file($fullPath)) {
            abort(404);
        }
        return response()->download($fullPath, $asset->filename, [
            'Content-Type' => $asset->mime ?? 'application/octet-stream',
        ]);
    })->name('admin.download-run-media');
});
