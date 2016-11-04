<?php

namespace Chassis\Console\Commands;

use Illuminate\Console\Command;

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
        /*
         * Flush the MetaData storage
         * @TODO: read config for desired storage
         */
        (new \Chassis\Integration\Redis\Storage())->flush();

        // Read and discard all updates
        $botsManager = resolve('chassis');
        $bot = $botsManager->bot();
        $bot->checkForUpdates(false, [], false);
    }
}
