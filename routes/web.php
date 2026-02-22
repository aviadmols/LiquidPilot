<?php

use App\Http\Controllers\TemplateEditorController;
use App\Http\Controllers\TemplatePreviewController;
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

    Route::get('/agent-runs/{run}/preview', [TemplatePreviewController::class, 'show'])->name('admin.template-preview');
    Route::get('/agent-runs/{run}/theme-assets/{path?}', function (int $run, string $path = '') {
        $agentRun = \App\Models\AgentRun::with('themeRevision')->findOrFail($run);
        $base = $agentRun->themeRevision?->extracted_path;
        if (!$base || !is_dir($base)) {
            abort(404);
        }
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $allowed = ['assets', 'config', 'layout', 'locales', 'sections', 'templates'];
        $first = explode('/', trim($path, '/'))[0] ?? '';
        if ($path !== '' && !in_array($first, $allowed, true)) {
            abort(404);
        }
        $fullPath = $base . \DIRECTORY_SEPARATOR . str_replace('/', \DIRECTORY_SEPARATOR, $path);
        $real = realpath($fullPath);
        if ($real === false || !is_file($real) || !str_starts_with($real, realpath($base))) {
            abort(404);
        }
        $mime = match (strtolower(pathinfo($real, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
        return response()->file($real, ['Content-Type' => $mime]);
    })->where('path', '.*')->name('admin.template-assets');

    Route::get('/agent-runs/{run}/template-editor', [TemplateEditorController::class, 'show'])->name('admin.template-editor');
    Route::post('/agent-runs/{run}/template-editor', [TemplateEditorController::class, 'updateCompose'])->name('admin.template-editor.update');
});
