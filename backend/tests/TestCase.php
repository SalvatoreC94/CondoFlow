<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum only activates session-based "stateful" auth (needed by
        // login/logout, which touch $request->session()) for requests whose
        // Origin/Referer matches a configured stateful domain. The test
        // client sends neither by default, so declare one here once for
        // every test instead of repeating it per request.
        $this->withHeader('Referer', config('app.frontend_url'));
    }
}
