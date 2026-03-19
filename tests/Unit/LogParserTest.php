<?php

namespace Willypelz\LogPlatform\Tests\Unit;

use Willypelz\LogPlatform\Services\LogParser;
use Willypelz\LogPlatform\Tests\TestCase;

class LogParserTest extends TestCase
{
    protected LogParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LogParser();
    }

    /** @test */
    public function it_can_parse_a_standard_log_line()
    {
        $line = '[2026-03-18 10:30:45] local.ERROR: Database connection failed';

        $result = $this->parser->parse($line);

        $this->assertTrue($result['success']);
        $this->assertNull($result['data']); // First line buffers

        // Flush buffer to get the entry
        $entry = $this->parser->flushBuffer();

        $this->assertNotNull($entry);
        $this->assertEquals('error', $entry['level']);
        $this->assertEquals('Database connection failed', $entry['message']);
    }

    /** @test */
    public function it_can_detect_log_start()
    {
        $this->assertTrue($this->parser->isLogStart('[2026-03-18 10:30:45] local.ERROR: Test'));
        $this->assertFalse($this->parser->isLogStart('    at SomeClass->method()'));
    }

    /** @test */
    public function it_handles_multiline_stack_traces()
    {
        $lines = [
            '[2026-03-18 10:30:45] local.ERROR: Exception occurred',
            'Stack trace:',
            '#0 /path/to/file.php(123): method()',
            '#1 /path/to/another.php(456): another()',
        ];

        foreach ($lines as $line) {
            $this->parser->parse($line);
        }

        $entry = $this->parser->flushBuffer();

        $this->assertStringContainsString('Stack trace:', $entry['message']);
        $this->assertStringContainsString('/path/to/file.php', $entry['message']);
    }

    /** @test */
    public function it_extracts_json_context()
    {
        $line = '[2026-03-18 10:30:45] local.ERROR: Test {"user_id":123,"action":"login"}';

        $this->parser->parse($line);
        $entry = $this->parser->flushBuffer();

        $this->assertEquals(['user_id' => 123, 'action' => 'login'], $entry['context']);
        $this->assertEquals('Test', $entry['message']); // Context removed from message
    }
}
use Willypelz\LogPlatform\Tests\TestCase;
use Willypelz\LogPlatform\Services\LogParser;

namespace Willypelz\LogPlatform\Tests\Unit;

