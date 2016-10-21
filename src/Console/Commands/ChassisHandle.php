<?php

namespace Chassis\Console\Commands;

use Chassis\Bot\BotsManager;
use Illuminate\Console\Command;
use Log;

class ChassisHandle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chassis:handle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read and handle updates';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment("Startup default bot");
        
        /** @var BotsManager **/
        $botsManager = resolve('chassis');
        
        $this->comment("Default Bot: " .$botsManager->getDefaultBot());
        
        $bot = $botsManager->bot();
        
        while(true){
            $start = microtime(true);
            $updates = $bot->checkForUpdates();
            $end = microtime(true);
            if(count($updates) > 0){
                $this->comment("Processed ".count($updates) ." in " . ($end-$start)*1000 ." ms");
            }
            foreach ($updates as $update) {                    
                $this->comment($this->getUpdateText($update));
            }
            
            usleep(100);
        }
    }
    
    function getUpdateText($update)
    {
        $id = $update->getUpdateId();
        $type = implode(".", \Chassis\Controller\ControllerBus::getUpdateHierarchy($update));
        return $id .": " .$type;
    }
    
    
}
