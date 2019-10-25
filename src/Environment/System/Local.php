<?php

namespace Cdev\Local\Environment\System;

use Creode\System\Command;

class Local extends Command
{
    const COMMAND = 'local';

    public function cleanup($path)
    {
        // $this->run(self::COMMAND, ['container', 'prune', '--force'], $path);
        // $this->run(self::COMMAND, ['image', 'prune', '--force'], $path);
        // $this->run(self::COMMAND, ['volume', 'prune', '--force'], $path);

        return 'Not implemented yet!';
    }

    public function pull($path, $image)
    {
        // $this->run(
        //     self::COMMAND,
        //     ['pull', $image],
        //     $path
        // );

        return 'Not implemented yet!';
    }
}
