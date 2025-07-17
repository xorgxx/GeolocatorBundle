<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GeolocatorBundle\Service\Storage;

#[AsCommand(
    name: 'geolocator:ban:list',
    description: 'Liste toutes les adresses IP bannies'
)]
class BanListCommand extends Command
{
    private $storage;
    private $enabled;

    public function __construct(Storage $storage, bool $enabled)
    {
        $this->storage = $storage;
        $this->enabled = $enabled;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->enabled) {
            $io->error('Le service de géolocalisation est désactivé.');
            return Command::FAILURE;
        }

        $bannedIps = $this->storage->getBannedIps();

        if (empty($bannedIps)) {
            $io->info('Aucune adresse IP n\'est actuellement bannie.');
            return Command::SUCCESS;
        }

        $io->title('Liste des adresses IP bannies');
        $io->table(
            ['IP', 'Raison', 'Date de bannissement'],
            array_map(function($ban) {
                return [
                    $ban['ip'],
                    $ban['reason'] ?? 'Non spécifiée',
                    isset($ban['timestamp']) ? date('Y-m-d H:i:s', $ban['timestamp']) : 'Inconnue'
                ];
            }, $bannedIps)
        );

        return Command::SUCCESS;
    }
}
