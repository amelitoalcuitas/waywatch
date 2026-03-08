<?php

namespace Tests;

use RuntimeException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        // Fail fast if tests are pointed at a likely non-test database.
        if ($connection !== 'sqlite' && ! str_contains(strtolower($database), 'test')) {
            throw new RuntimeException("Unsafe test database configuration: {$connection} / {$database}. Use sqlite :memory: or a dedicated *_test database.");
        }
    }
}
