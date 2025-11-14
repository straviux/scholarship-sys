<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        Inertia::share([
            //....
            'urlPrev'    => function () {
                if (url()->previous() !== route('login') && url()->previous() !== '' && url()->previous() !== url()->current()) {
                    return url()->previous();
                } else {
                    return 'empty'; // used in javascript to disable back button behavior
                }
            },

        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // Automatically check permissions using Spatie Permission package
        Gate::before(function ($user, $ability) {
            // Administrator has access to everything
            if ($user->hasRole('administrator')) {
                return true;
            }

            // Check if user has the specific permission
            try {
                if ($user->hasPermissionTo($ability)) {
                    return true;
                }
            } catch (\Exception $e) {
                // Permission doesn't exist, let other gates handle it
            }
            return null;
        });

        // Define Gates
        Gate::define('admin', function ($user) {
            return $user->hasRole('administrator');
        });

        // Gate::define('create-scholar-profile', function ($user) {
        //     return $user->hasRole('administrator') || $user->hasRole('moderator');
        // });

        try {
            Storage::extend('google', function ($app, $config) {
                $options = [];

                if (!empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                if (!empty($config['sharedFolderId'] ?? null)) {
                    $options['sharedFolderId'] = $config['sharedFolderId'];
                }

                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            // your exception handling logic
        }
    }
}
