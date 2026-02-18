<?php

namespace Tests\Unit\Agent;

use App\Domain\Agent\AIProgressLogger;
use App\Models\AgentRun;
use App\Models\AIActionLog;
use App\Models\Project;
use App\Models\ThemeRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIProgressLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_writes_to_ai_action_logs(): void
    {
        $project = Project::create(['name' => 'P', 'locale' => 'en', 'status' => 'active']);
        $revision = ThemeRevision::create([
            'project_id' => $project->id,
            'original_filename' => 'theme.zip',
            'zip_path' => 'themes/1/theme.zip',
            'status' => 'ready',
        ]);
        $run = AgentRun::create([
            'project_id' => $project->id,
            'theme_revision_id' => $revision->id,
            'mode' => 'full',
            'status' => 'running',
        ]);

        $logger = AIProgressLogger::forRun($run->id);
        $logger->logStart('plan', 'Starting AI call', ['model' => 'test']);
        $logger->logValidation('plan', true);
        $logger->logError('plan', 'Something failed');

        $logs = AIActionLog::where('agent_run_id', $run->id)->orderBy('id')->get();
        $this->assertCount(3, $logs);
        $this->assertSame('info', $logs[0]->level);
        $this->assertSame('Starting AI call', $logs[0]->message);
        $this->assertSame('info', $logs[1]->level);
        $this->assertSame('error', $logs[2]->level);
    }
}
