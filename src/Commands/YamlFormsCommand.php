<?php

namespace Waka\YamlForms\Commands;

use Illuminate\Console\Command;

class YamlFormsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'make:yamlForm
    {name : The name of the model}
    ';

    /**
     * @var string
     */
    protected $description = 'Create a yamlForm';

    /**
     * @var string
     */
    protected $type = 'Service';

    public function handle(): int {
        $name = ucfirst($this->argument('name'));

        // Artisan::call('make:yamlFormModel', [
        //     'name' => $name,
        // ]);

        \Artisan::call('make:yamlFormController', [
            'name' => $name,
        ]);

        $this->info('Controller, YAML file, and model created successfully.');

        return self::SUCCESS;
    }
}
