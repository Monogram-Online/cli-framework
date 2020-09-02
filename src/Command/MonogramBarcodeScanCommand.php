<?php

namespace Monogram\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class MonogramBarcodeScanCommand extends Command
{
    protected static $defaultName = 'monogram:barcode:scan';

    protected $kernel;
    private $_runningProcesses;

    public function __construct(string $name = null, KernelInterface $kernel = null)
    {
        $this->kernel = $kernel;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Controls Barcode processing tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();
        $projectRoot = $this->kernel->getProjectDir();
        $scanPrompt = '';
        $i = 0;
        $runningProcesses = [];
        declare(ticks = 1);

        pcntl_signal(SIGINT, [$this, '_doInterrupt']);
        pcntl_signal(SIGTERM, [$this, '_doTerminate']);

        while ($scanPrompt !== 'exit'){
            $scanPrompt = $io->ask('Barcode');
            $process = new Process(
                [$phpBinaryPath, $projectRoot . '/bin/console', 'monogram:barcode:process', $scanPrompt]
            );
            $process->setTimeout(3600);
            $io->caution('Starting thread-process ' . $i);
            $process->start();
            $this->_runningProcesses[$i] = $process;
            $i++;
        }
        return Command::SUCCESS;
    }

    /**
     * Ctrl-C
     */
    private function _doInterrupt()
    {
        $this->_waitToFinishAllSubProcesses();
        exit;
    }

    /**
     * kill PID
     */
    private function _doTerminate()
    {
        $this->_waitToFinishAllSubProcesses();
        exit;
    }

    private function _waitToFinishAllSubProcesses(){
        while (count($this->_runningProcesses)) {
            foreach ($this->_runningProcesses as $i => $runningProcess) {
                // specific process is finished, so we remove it
                if (! $runningProcess->isRunning()) {
                    echo "Shutting down thread-process $i \n";
                    unset($this->_runningProcesses[$i]);
                }
            }
        }
    }
}
