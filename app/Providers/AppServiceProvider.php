<?php

namespace Ushahidi\App\Providers;

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
        $this->app->configure('cdn');
        $this->app->configure('filesystems');
        $this->app->configure('media');
        $this->app->configure('ratelimiter');
        $this->app->configure('multisite');
        $this->app->configure('ohanzee-db');
        $this->app->configure('services');

        $this->registerServicesFromAura();

        $this->registerFilesystem();
        $this->registerMailer();

        $this->registerDataSources();

        $this->setupMultisiteIlluminateDB();

        $this->registerFeatures();
    }

    public function registerServicesFromAura()
    {
        $this->app->singleton(\Ushahidi\Factory\UsecaseFactory::class, function ($app) {
            // Just return it from AuraDI
            return service('factory.usecase');
        });

        $this->app->singleton(\Ushahidi\Core\Entity\MessageRepository::class, function ($app) {
            // Just return it from AuraDI
            return service('repository.message');
        });

        $this->app->singleton(\Ushahidi\Core\Entity\ContactRepository::class, function ($app) {
            // Just return it from AuraDI
            return service('repository.contact');
        });

        $this->app->singleton(\Ushahidi\Core\Entity\PostRepository::class, function ($app) {
            // Just return it from AuraDI
            return service('repository.post');
        });

        $this->app->singleton(\Ushahidi\Core\Entity\TargetedSurveyStateRepository::class, function ($app) {
            // Just return it from AuraDI
            return service('repository.targeted_survey_state');
        });

        $this->app->singleton(\Ushahidi\Core\Entity\FormAttributeRepository::class, function ($app) {
            // Just return it from AuraDI
            return service('repository.form_attribute');
        });

        $this->app->singleton(\Ushahidi\Core\Tool\Verifier::class, function ($app) {
            // Just return it from AuraDI
            return service('tool.verifier');
        });
    }

    public function registerMailer()
    {
        // Add mailer
        $this->app->singleton('mailer', function ($app) {
            return $app->loadComponent(
                'mail',
                \Illuminate\Mail\MailServiceProvider::class,
                'mailer'
            );
        });
    }

    public function registerFilesystem()
    {
        // Add filesystem
        $this->app->singleton('filesystem', function ($app) {
            return $app->loadComponent(
                'filesystems',
                \Illuminate\Filesystem\FilesystemServiceProvider::class,
                'filesystem'
            );
        });
    }

    public function registerDataSources()
    {
        $this->app->register(\Ushahidi\App\DataSource\DataSourceServiceProvider::class);
    }

    protected function getDbConfig()
    {
        // Kohana injection
        // DB config
        $config = config('ohanzee-db');
        $config = $config['default'];

        // Is this a multisite install?
        $multisite = config('multisite.enabled');
        if ($multisite) {
            $config = service('multisite')->getDbConfig();
        }

        return $config;
    }

    protected function getClientUrl($config, $multisite)
    {
        $clientUrl = env('CLIENT_URL', false);

        if (env("MULTISITE_DOMAIN", false)) {
            try {
                $clientUrl = $multisite()->getClientUrl();
            } catch (Exception $e) {
            }
        }

        // Or overwrite from config
        if (!$clientUrl && $config['client_url']) {
            $client_url = $config['client_url'];
        }

        return $clientUrl;
    }

    protected function setupMultisiteIlluminateDB()
    {
        $config = $this->getDbConfig();

        $existing = config('database.connections.mysql');

        config(['database.connections.mysql' => [
            'database'  => $config['connection']['database'],
            'username'  => $config['connection']['username'],
            'password'  => $config['connection']['password'],
            'host'      => $config['connection']['hostname'],
        ] + $existing]);
    }

    public function registerFeatures()
    {
        $this->app->singleton('features', function ($app) {
            return new \Ushahidi\App\Tools\Features(service('repository.config'));
        });
    }
}
