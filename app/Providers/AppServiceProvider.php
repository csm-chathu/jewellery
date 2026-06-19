<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->switchDatabaseByDomain();
    }

    private function switchDatabaseByDomain(): void
    {
        $host    = request()->getHost();
        $tenants = config('tenants');

        if (isset($tenants[$host])) {
            Config::set('database.connections.mysql.database', $tenants[$host]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
    }
}
