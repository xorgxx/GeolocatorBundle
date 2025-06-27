<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GeolocatorBundle\Service\ProviderManager;

class CheckDsnCommand extends Command
{
    protected static $defaultName = 'xorg:geolocator:check-dsn';

    private ProviderManager $providerManager;

    public function __construct(ProviderManager $providerManager)
    {
        parent::__construct();
        $this->providerManager = $providerManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->providerManager->getProviders() as $provider) {
            $output->writeln('Provider: '.$provider->getName());
        }
        return Command::SUCCESS;
    }
}
