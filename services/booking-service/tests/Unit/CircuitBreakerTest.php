<?php

namespace Tests\Unit;

use App\Support\CircuitBreaker;
use Tests\TestCase;

// Uses the Laravel TestCase because the breaker keeps its state in the cache.
class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $breaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->breaker = new CircuitBreaker('payment');
    }

    private function failTimes(int $times): void
    {
        for ($count = 1; $count <= $times; $count++) {
            $this->breaker->recordFailure();
        }
    }

    public function test_starts_closed_and_allows_calls(): void
    {
        $this->assertSame(CircuitBreaker::CLOSED, $this->breaker->state());
        $this->assertTrue($this->breaker->allowsCall());
    }

    public function test_four_failures_keep_it_closed(): void
    {
        $this->failTimes(4);

        $this->assertSame(CircuitBreaker::CLOSED, $this->breaker->state());
    }

    public function test_five_failures_open_it_and_block_calls(): void
    {
        $this->failTimes(5);

        $this->assertSame(CircuitBreaker::OPEN, $this->breaker->state());
        $this->assertFalse($this->breaker->allowsCall());
    }

    public function test_failures_older_than_60_seconds_are_forgotten(): void
    {
        $this->failTimes(4);
        $this->travel(61)->seconds();
        $this->failTimes(1);

        $this->assertSame(CircuitBreaker::CLOSED, $this->breaker->state());
    }

    public function test_after_30_seconds_only_one_trial_call_is_allowed(): void
    {
        $this->failTimes(5);
        $this->travel(31)->seconds();

        $this->assertSame(CircuitBreaker::HALF_OPEN, $this->breaker->state());
        $this->assertTrue($this->breaker->allowsCall());
        $this->assertFalse($this->breaker->allowsCall());
    }

    public function test_successful_trial_closes_it(): void
    {
        $this->failTimes(5);
        $this->travel(31)->seconds();
        $this->breaker->allowsCall();

        $this->breaker->recordSuccess();

        $this->assertSame(CircuitBreaker::CLOSED, $this->breaker->state());
        $this->assertTrue($this->breaker->allowsCall());
    }

    public function test_failed_trial_opens_it_again(): void
    {
        $this->failTimes(5);
        $this->travel(31)->seconds();
        $this->breaker->allowsCall();

        $this->breaker->recordFailure();

        $this->assertSame(CircuitBreaker::OPEN, $this->breaker->state());
        $this->assertFalse($this->breaker->allowsCall());
    }

    public function test_state_is_shared_between_breaker_objects(): void
    {
        // Two objects = like two booking pods. Both read the same cache.
        $this->failTimes(5);

        $this->assertSame(CircuitBreaker::OPEN, (new CircuitBreaker('payment'))->state());
    }
}
