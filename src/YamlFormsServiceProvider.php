<?php

namespace Waka\YamlForms;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Waka\YamlForms\Commands\YamlFormsCommand;
use Waka\YamlForms\Commands\Files\CreateYamlFormsModel;
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
        $package
            ->name('yamlforms')
            // ->hasConfigFile()
            // ->hasViews()
            // ->hasMigration('create_yamlforms_table')
            ->hasCommand(CreateYamlFormsController::class)
            ->hasCommand(YamlFormsCommand::class);

    }
}
