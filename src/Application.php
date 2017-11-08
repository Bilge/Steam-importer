<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

final class Application
{
    public function start(): void
    {
        (new Cli)->run();
    }
}
