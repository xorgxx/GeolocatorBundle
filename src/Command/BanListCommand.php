<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BanListCommand extends Command
{
    protected static \$defaultName = 'xorg:geolocator:ban:list';

    protected function execute(InputInterface \$input, OutputInterface \$output): int
    {
        // TODO: lister les IP bannies depuis la session
        \$output->writeln('Liste des IP bannies:');
        return Command::SUCCESS;
    }
}
