<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GeolocatorBundle\Service\BanManager;

class BanAddCommand extends Command
{
    protected static $defaultName = 'neox:geolocator:ban:add';

    private BanManager $banManager;

    public function __construct(BanManager $banManager)
    {
        parent::__construct();
        $this->banManager = $banManager;
    }

    protected function configure()
    {
        $this->addArgument('ip', InputArgument::REQUIRED, 'IP address')
             ->addArgument('duration', InputArgument::OPTIONAL, 'Duration (e.g. "3 hours")', '3 hours');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ip = $input->getArgument('ip');
        $duration = $input->getArgument('duration');
        $this->banManager->addBan($ip, 'Manual ban', $duration);
        $output->writeln("IP $ip bannie pour $duration.");
        return Command::SUCCESS;
    }
}
