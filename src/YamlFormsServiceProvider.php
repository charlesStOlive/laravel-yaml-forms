<?php

namespace Waka\YamlForms;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Waka\YamlFormsConsole\Commands\YamlFormsCommand;
use Waka\YamlForms\Commands\Files\CreateYamlFormsController;
class YamlFormsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name('yamlforms');
    }
}
