<?php

namespace Chassis\Console\Commands;

use Chassis\Bot\BotsManager;
use Chassis\Controller\ControllerBus;
use Illuminate\Console\Command;
use Telegram\Bot\Objects\Update;

class ChassisHandle extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chassis:handle {bot?} {--loop} {--timeout=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read and handle updates';

    /**
     * Check and process telegram updates
     *
     * @return mixed
     */
    public function handle()
    {

        $loop = $this->option('loop');

        $timeout = $this->option('timeout');

        /** @var BotsManager * */
        $botsManager = resolve('chassis');

        $botName = null;

        if($this->argument('bot') != null){
            $botName = $this->argument('bot');
            $this->comment("Starting " .$botName);
        }else{
            $this->comment("Starting default bot: " .$botsManager->getDefaultBot());
        }

        $bot = $botsManager->bot($botName);

        do {
            // Long poll for updates
            $updates = $bot->checkForUpdates(false, ['timeout' => $timeout]);

            if (count($updates) > 0) {
                $this->comment("Processed " . count($updates));
            } else {
                $this->comment("Timed out");
            }
            foreach ($updates as $update) {
                $this->comment($this->getUpdateText($update));
            }
        } while ($loop);
    }

    /**
     * Short update description
     *
     * @param Update $update
     * @return string
     */
    private function getUpdateText(Update $update)
    {
        $id = $update->getUpdateId();
        $type = implode(".", ControllerBus::getUpdateHierarchy($update));
        return $id . ": " . $type;
    }
}
