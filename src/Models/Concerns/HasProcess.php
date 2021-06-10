<?php

namespace Spatie\ServerMonitor\Models\Concerns;

use Spatie\ServerMonitor\Manipulators\Manipulator;
use Symfony\Component\Process\Process;

trait HasProcess
{
    public function getProcess(): Process
    {
        return blink()->once("process.{$this->id}", function () {
            $process = Process::fromShellCommandline($this->getProcessCommand());

            $process->setTimeout($this->getDefinition()->timeoutInSeconds());

            $manipulator = app(Manipulator::class);

            return $manipulator->manipulateProcess($process, $this);
        });
    }

    public function getProcessCommand(): string
    {
        $definition = $this->getDefinition();

        $portArgument = empty($this->host->port) ? '' : "-p {$this->host->port}";

        $sshCommandPrefix = config('server-monitor.ssh_command_prefix');
        $sshCommandSuffix = config('server-monitor.ssh_command_suffix');

        $result = 'ssh';
        if ($sshCommandPrefix) {
            $result .= ' '.$sshCommandPrefix;
        }
        $result .= ' '.$this->getTarget();
        if ($portArgument) {
            $result .= ' '.$portArgument;
        }
        if ($sshCommandSuffix) {
            $result .= ' '.$sshCommandSuffix;
        }
        $result .= " '".$definition->command().PHP_EOL."'";

        return $result;
    }

    protected function getTarget(): string
    {
        $target = empty($this->host->ip)
            ? $this->host->name
            : $this->host->ip;

        if ($this->host->ssh_user) {
            $target = $this->host->ssh_user.'@'.$target;
        }

        return $target;
    }
}
