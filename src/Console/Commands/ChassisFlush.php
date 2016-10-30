<?php

namespace Chassis\Console\Commands;

use Chassis\Bot\BotsManager;
use Illuminate\Console\Command;
use Log;

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
        (new \Chassis\Integration\Redis\Storage())->flush();
        
        $botsManager = resolve('chassis');
        
        $bot = $botsManager->bot();
        $bot->checkForUpdates(false, [], false);
    }
    
    
    
}
