<?php

namespace Cdev\Local\Environment\System;

use Creode\System\Command;

class Local extends Command
{
    const COMMAND = 'local';

    public function cleanup($path)
    {
        return 'Not implemented in this environment!';
    }

    public function pull($path, $image)
    {
        return 'Not implemented in this environment!';
    }
}
