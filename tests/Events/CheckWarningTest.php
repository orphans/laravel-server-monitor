<?php

namespace Spatie\ServerMontior\Test\Events;

use Event;
use Illuminate\Support\Facades\Artisan;
use Spatie\ServerMonitor\Events\CheckWarning;
use Spatie\ServerMonitor\Test\TestCase;

class CheckWarningTest extends TestCase
{
    /** @var \Spatie\ServerMonitor\Models\Check */
    protected $check;

    public function setUp(): void
    {
        parent::setUp();

        $this->skipIfDummySshServerIsNotRunning();

        Event::fake();

        $this->check = $this->createHost()->checks->first();
    }

    /** @test */
    public function the_succeeded_event_will_be_fired_when_a_check_succeeds()
    {
        $this->letSshServerRespondWithDiskspaceUsagePercentage(85);

        Event::assertNotDispatched(CheckWarning::class);

        Artisan::call('server-monitor:run-checks');

        Event::assertDispatched(CheckWarning::class, function (CheckWarning $event) {
            return $event->check->id === $this->check->id;
        });
    }
}
