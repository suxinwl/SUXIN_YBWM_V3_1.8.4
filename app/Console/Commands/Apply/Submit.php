<?php

namespace App\Console\Commands\Apply;

use App\Models\Admin\Apply;
use Illuminate\Console\Command;

class Submit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apply:submit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $applyCount = Apply::applyTotal();
            $domain_url = preg_replace("(^https?://)", "", env('APP_URL'));
            $request = ['domain_url' => $domain_url, 'auth_type' => 1, 'applyCount' => $applyCount];
            $url = config('app.authorizeDomain') . '/cloud/safe/changePlatformsNumber';
            $data = httpRequest($url, $request);
        } catch (\Exception $e) {
        }
    }
}
