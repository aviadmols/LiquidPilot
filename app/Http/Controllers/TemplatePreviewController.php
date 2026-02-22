<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplatePreviewController extends Controller
{
    /**
     * Show template preview (sections from compose step output_json).
     */
    public function show(Request $request, int $runId): View|array
    {
        $run = AgentRun::with(['themeRevision'])->findOrFail($runId);
        $composeStep = $run->agentSteps()->where('step_key', 'compose')->latest('id')->first();
        $indexJson = $composeStep?->output_json ?? ['sections' => [], 'order' => []];

        $assetsBaseUrl = url('admin/agent-runs/' . $run->id . '/theme-assets');

        if ($request->wantsJson()) {
            return ['indexJson' => $indexJson, 'assetsBaseUrl' => $assetsBaseUrl];
        }

        return view('template-preview.show', [
            'run' => $run,
            'indexJson' => $indexJson,
            'assetsBaseUrl' => rtrim($assetsBaseUrl, '/'),
        ]);
    }
}
