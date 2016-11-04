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

        /** @var BotsManager * */
        $botsManager = resolve('chassis');

        $this->comment("Default Bot: " . $botsManager->getDefaultBot());

        $bot = $botsManager->bot();

        while (true) {

            // Long poll for updates
            $updates = $bot->checkForUpdates(false, ['timeout' => 60]);

            if (count($updates) > 0) {
                $this->comment("Processed " . count($updates));
            } else {
                $this->comment("Timed out -> Start next long poll");
            }
            foreach ($updates as $update) {
                $this->comment($this->getUpdateText($update));
            }
        }
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
