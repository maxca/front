<?php

namespace Samark\Front\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CopyEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'samark:copy-env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy environment file.';

    /**
     * winner list
     * @var collection
     */
    protected $generate;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        Log::info('start process ' . get_class());
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $env = "\r\n";
        $env .= "MEMBER_COOKIE=\r\n";
        $env .= "COOKIE_DOMAIN=\r\n";
        $env .= "EXPIRE_TIME=1440\r\n";

        if (file_exists(base_path() . '/.env')) {
            file_put_contents(base_path() . '/.env', $env, FILE_APPEND);
            $this->line("copy .env config success !");
        } else {
            $this->line('not found .env file');
        }

    }
}
