<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GeolocatorBundle\Service\BanManager;

class BanRemoveCommand extends Command
{
    protected static \$defaultName = 'xorg:geolocator:ban:remove';

    private BanManager \$banManager;

    public function __construct(BanManager \$banManager)
    {
        parent::__construct();
        \$this->banManager = \$banManager;
    }

    protected function configure()
    {
        \$this->addArgument('ip', InputArgument::REQUIRED, 'IP address');
    }

    protected function execute(InputInterface \$input, OutputInterface \$output): int
    {
        \$ip = \$input->getArgument('ip');
        \$this->banManager->removeBan(\$ip);
        \$output->writeln("IP \$ip débannie.");
        return Command::SUCCESS;
    }
}
