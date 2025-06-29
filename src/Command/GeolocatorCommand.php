<?php
// src/Command/GeolocatorCommand.php
declare(strict_types=1);

namespace App\Command;

use GeolocatorBundle\Service\AsyncGeolocator;
use GeolocatorBundle\Service\GeolocatorService;
//use MongoDB\Driver\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GeolocatorCommand extends Command
{
    protected static $defaultName        = 'app:geolocate';
    protected static $defaultDescription = 'Geolocate an IP address (sync or async).';

    private AsyncGeolocator   $asyncGeolocator;
    private GeolocatorService $geolocatorService;

    public function __construct(AsyncGeolocator $asyncGeolocator, GeolocatorService $geolocatorService)
    {
        parent::__construct();
        $this->asyncGeolocator = $asyncGeolocator;
        $this->geolocatorService = $geolocatorService;
    }

    protected function configure(): void
    {
        $this->addArgument('ip', InputArgument::REQUIRED, 'IP address to geolocate')
             ->addOption('async', null, InputOption::VALUE_NONE, 'If specified, sends geolocation request asynchronously via Messenger');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ip = $input->getArgument('ip');
        $useAsync = $input->getOption('async');

        if ($useAsync) {
            $data = $this->asyncGeolocator->geolocate($ip);
            if ($data !== null) {
                $io->success('Data found in cache:');
                $io->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            else {
                $io->info('Async geolocation requested: message has been dispatched to Messenger bus.');
                $io->comment('Start a worker: bin/console messenger:consume async');
            }
        }
        else {
            try {
                $result = $this->geolocatorService->locateIp($ip);
                $io->success('Sync geolocation successful:');
                $io->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                $io->error('Error during sync geolocation: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}