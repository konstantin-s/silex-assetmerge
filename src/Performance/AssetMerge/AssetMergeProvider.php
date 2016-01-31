<?php

namespace Performance\AssetMerge;

use Silex\Application;
use Silex\ServiceProviderInterface;

class AssetMergeProvider implements ServiceProviderInterface {

    public function register(Application $app) {

        $app['assetmerge_config'] = $app->share(function () use ($app) {
            return new Config($app);
        });

        $app['assetmerge_merger'] = $app->share(function () use ($app) {
            return new Merger($app);
        });
    }

    public function boot(Application $app) {
        
    }

}
