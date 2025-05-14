<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will delete all the expired tokens which were created for registrtaions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('new_registration_token')->where('expiry_at', '<', now())->delete();
    }
}
