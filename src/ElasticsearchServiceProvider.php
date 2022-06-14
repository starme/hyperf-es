<?php
namespace Starme\HyperfEs;

use Illuminate\Support\ServiceProvider;

class ElasticServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Eloquent\Model::setConnectionResolver($this->app['es']);

        Eloquent\Eloquent::setConnectionResolver($this->app['es']);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('es', function ($app){
            return new ConnectionResolver($app);
        });

        $this->app->singleton('es.connection', function ($app){
            return $app['elastic.search']->connection();
        });

        $this->app->singleton('es.schema', function ($app){
            return $app['elastic.connection']->getSchemaBuilder();
        });
    }

}