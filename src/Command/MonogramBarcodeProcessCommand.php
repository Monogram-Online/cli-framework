<?php

namespace Monogram\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MonogramBarcodeProcessCommand extends Command
{
    protected static $defaultName = 'monogram:barcode:process';

    private ?HttpClientInterface $_httpClient;
    protected ?ParameterBagInterface $params;

    public function __construct(?string $name = null, ?HttpClientInterface $httpClient, ?ParameterBagInterface $params)
    {
        $this->_httpClient = $httpClient;
        $this->params = $params;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Process barcode input.')
            ->addArgument('barcode', InputArgument::REQUIRED, 'Barcode input')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $this->_httpClient->request(
                'POST',
                $this->params->get('api.endpoint').$this->params->get('api.resource.barcode'),
                [
                    'json' => ['barcodeValue' => $input->getArgument('barcode')]
                ]
            );
        } catch (TransportExceptionInterface $e) {
        }

        return Command::SUCCESS;
    }
}
