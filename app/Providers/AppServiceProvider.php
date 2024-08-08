<?php

namespace App\Providers;


use App\Models\User;
use App\Models\Mailsetting;
use PSpell\Config as PSpellConfig;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 00webhost ar jonno
        // $this->app->bind('path.public', function () {
        //    return base_path('public_html');
        // });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Admin User
        Gate::define('isAdmin', function(User $user){
            return $user->role === 'admin';
        });
        
        // Current User check User Id
        Gate::define('currentUser', function(User $user, $userId){
            return $user->id === intval($userId);
        });
    }
}
