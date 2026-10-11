<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Tests use local auth mode (phpunit.xml), so these headers act as a logged in user.
    protected function visitorHeaders(): array
    {
        return ['X-Local-User' => 'local-visitor-1'];
    }

    protected function adminHeaders(): array
    {
        return ['X-Local-User' => 'local-admin-1', 'X-Local-Groups' => 'admins'];
    }
}
