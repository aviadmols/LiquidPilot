<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use App\Models\AgentStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateEditorController extends Controller
{
    public function show(Request $request, int $runId): View
    {
        $run = AgentRun::with(['themeRevision'])->findOrFail($runId);
        $composeStep = $run->agentSteps()->where('step_key', 'compose')->latest('id')->first();
        $indexJson = $composeStep?->output_json ?? ['sections' => [], 'order' => []];
        $order = $indexJson['order'] ?? array_keys($indexJson['sections'] ?? []);
        $sections = $indexJson['sections'] ?? [];
        $selectedId = $request->query('section', $order[0] ?? null);
        $selectedSection = $selectedId ? ($sections[$selectedId] ?? null) : null;

        $previewUrl = route('admin.template-preview', ['run' => $run->id]);
        $backUrl = \App\Filament\Resources\AgentRuns\AgentRunResource::getUrl('view', ['record' => $run]);

        return view('template-editor.show', [
            'run' => $run,
            'composeStep' => $composeStep,
            'indexJson' => $indexJson,
            'order' => $order,
            'sections' => $sections,
            'selectedId' => $selectedId,
            'selectedSection' => $selectedSection,
            'previewUrl' => $previewUrl,
            'backUrl' => $backUrl,
        ]);
    }

    public function updateCompose(Request $request, int $runId): RedirectResponse
    {
        $run = AgentRun::findOrFail($runId);
        $composeStep = $run->agentSteps()->where('step_key', 'compose')->latest('id')->first();
        if (!$composeStep) {
            return redirect()->route('admin.template-editor', ['run' => $runId])
                ->with('error', 'No compose step found.');
        }

        $sectionId = $request->input('section_id');
        $settings = $request->input('settings', []);
        if (!is_string($sectionId) || $sectionId === '') {
            return redirect()->route('admin.template-editor', ['run' => $runId])
                ->with('error', 'Invalid section.');
        }

        $output = $composeStep->output_json ?? ['sections' => [], 'order' => []];
        if (!isset($output['sections'][$sectionId])) {
            return redirect()->route('admin.template-editor', ['run' => $runId])
                ->with('error', 'Section not found.');
        }

        $normalized = [];
        foreach (is_array($settings) ? $settings : [] as $k => $v) {
            if ($v === '1' || $v === 'true') {
                $normalized[$k] = true;
            } elseif ($v === '0' || $v === 'false') {
                $normalized[$k] = false;
            } elseif (is_numeric($v)) {
                $normalized[$k] = str_contains((string) $v, '.') ? (float) $v : (int) $v;
            } elseif (is_string($v) && (str_starts_with(trim($v), '[') || str_starts_with(trim($v), '{'))) {
                $decoded = json_decode($v, true);
                $normalized[$k] = $decoded !== null ? $decoded : $v;
            } else {
                $normalized[$k] = $v;
            }
        }
        $output['sections'][$sectionId]['settings'] = $normalized;
        $composeStep->update(['output_json' => $output]);

        return redirect()->route('admin.template-editor', ['run' => $runId, 'section' => $sectionId])
            ->with('success', 'Settings saved. Refresh the preview to see changes.');
    }
}
