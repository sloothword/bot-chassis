<?php

namespace Chassis\Console\Commands;

use Chassis;
use Illuminate\Console\Command;

class ChassisMe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chassis:me';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get my profile from Telegram';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $response = Chassis::bot()->getTelegram()->getMe();
        $this->comment("Hi, my name is " .$response->getFirstName() ." " .$response->getUsername() ." (" .$response->getId() .")");
    }
}
