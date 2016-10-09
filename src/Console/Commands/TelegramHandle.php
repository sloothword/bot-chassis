<?php

namespace Chassis\Console\Commands;

use Chassis\Bot\BotsManager;
use Illuminate\Console\Command;
use Log;

class TelegramHandle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:handle';

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
        
        $updates = $botsManager->bot()->checkForUpdates();
        
        $this->comment(count($updates) ." Updates handled");
//        Log::info('getUpdates');
//        
//        while(true){
//            
//            $updates = ->checkForUpdates();
//            if($updates){
//                $this->comment("Processed " .count($updates) ." Updates: ");
//                foreach ($updates as $update) {                    
//                    $this->comment($update->getUpdateId());
//                }
//            }
//        
//            usleep(100);
//        }
        
        
    }
}
