<?php

namespace Cdev\Local\Environment\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallDependenciesCommand extends Command {
    protected function configure()
    {
        $this->setName('env:setup-dependencies');
        $this->setHidden(false);
        $this->setDescription('Sets up the local environment dependencies');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $script_path = __DIR__ . '/../../../install.sh';
        $process = new Process($script_path);

        // Sets a script timeout to 10 minutes.
        $process->setTimeout(10 * 60);

        // Runs the process and streams the output to screen.
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        return 0;
    }
}