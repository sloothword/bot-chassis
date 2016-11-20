<?php

namespace Chassis\Console\Commands;

use Chassis\Integration\StorageInterface;
use Illuminate\Console\Command;
use Telegram\Bot\BotsManager;

class ChassisFlush extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chassis:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all pending updates and the MetaData storage';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        /** @var BotsManager */
        $botsManager = resolve('chassis');

        // Flush the MetaData storage
        $botsManager->getContainer()->make(StorageInterface::class)->flush();

        // Read and discard all updates
        $bot = $botsManager->bot();
        $bot->checkForUpdates(false, [], false);
    }
}
