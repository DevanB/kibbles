<?php

declare(strict_types=1);

namespace Illuminate\Testing;

use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Testing\Concerns\RunsInParallel;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\TextUI\Configuration\PhpHandler;

/**
 * Temporary ParallelRunner until laravel/framework can move past ~13.34.0.
 *
 * PHPUnit 13.4 requires PhpHandler(Emitter). Laravel's fix shipped in 13.35
 * (laravel/framework#61859). This project cannot take 13.35 until Wayfinder
 * accepts HTTP QUERY (laravel/wayfinder#324). Delete this class, its classmap
 * entry, and the exclude-from-classmap when that pin is lifted.
 */
final class ParallelRunner implements \ParaTest\RunnerInterface
{
    use RunsInParallel;

    public function run(): int
    {
        return $this->execute();
    }

    public function execute(): int
    {
        $configuration = $this->options instanceof \ParaTest\Options
            ? $this->options->configuration
            : $this->options->configuration();

        (new PhpHandler(EventFacade::emitter()))->handle($configuration->php());

        $this->forEachProcess(function (): void {
            ParallelTesting::callSetUpProcessCallbacks();
        });

        try {
            $potentialExitCode = $this->runner->run();
        } finally {
            $this->forEachProcess(function (): void {
                ParallelTesting::callTearDownProcessCallbacks();
            });
        }

        return $potentialExitCode ?? $this->getExitCode();
    }
}
