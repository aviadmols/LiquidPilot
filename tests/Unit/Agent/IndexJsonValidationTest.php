<?php

namespace Tests\Unit\Agent;

use App\Domain\Agent\JsonGuard;
use PHPUnit\Framework\TestCase;

class IndexJsonValidationTest extends TestCase
{
    private JsonGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new JsonGuard;
    }

    public function test_valid_index_json_passes(): void
    {
        $json = '{"sections":{"section_1":{"type":"header","settings":{}},"section_2":{"type":"footer","settings":{}}},"order":["section_1","section_2"]}';
        $decoded = $this->guard->decode($json);
        $this->assertIsArray($decoded);
        $err = $this->guard->validate($decoded, ['required_keys' => ['sections', 'order']]);
        $this->assertNull($err);
    }

    public function test_missing_required_key_fails(): void
    {
        $decoded = ['sections' => []];
        $err = $this->guard->validate($decoded, ['required_keys' => ['sections', 'order']]);
        $this->assertNotNull($err);
        $this->assertStringContainsString('order', $err);
    }

    public function test_invalid_json_decode_returns_null(): void
    {
        $this->assertNull($this->guard->decode('not json'));
        $this->assertNull($this->guard->decode('{"broken":'));
    }
}
