<?php

namespace Larapress\Cron;

/**
 * A recurring job. Subclasses declare the hook, the recurrence and run().
 *
 * Instantiation registers the handler; schedule() is called on activation and
 * again on admin_init, so a job added in a plugin update still gets scheduled.
 */
abstract class BaseCron
{
    abstract public function hook(): string;

    abstract public function run(): void;

    /** Any recurrence registered with WordPress: hourly, daily, weekly… */
    public function recurrence(): string
    {
        return 'daily';
    }

    /** Delay before the first run, so activation does not fire it instantly. */
    public function firstRunDelay(): int
    {
        return HOUR_IN_SECONDS;
    }

    public function __construct()
    {
        add_action($this->hook(), [$this, 'run']);
    }

    public function schedule(): void
    {
        if (!wp_next_scheduled($this->hook())) {
            wp_schedule_event(time() + $this->firstRunDelay(), $this->recurrence(), $this->hook());
        }
    }

    public function unschedule(): void
    {
        wp_clear_scheduled_hook($this->hook());
    }
}
