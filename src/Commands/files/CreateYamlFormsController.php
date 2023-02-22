<?php

declare(strict_types=1);

namespace Waka\YamlForms\Commands\Files;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

final class CreateYamlFormsController extends GeneratorCommand
{
    /**
     * @var string
     */
    protected $name = 'make:yamlFormController';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__.'/../../../stubs/yamlforms-controller.stub';
    }

    /**
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return "{$rootNamespace}\\Http\\Controllers";
    }

    /**
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getModelSpaceName($className): string
    {
        return "App\\Models\\{$className}";
    }


    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name): string
    {
        $controllerName = "{$name}Controller";
        $className = str_replace($this->getNamespace($controllerName).'\\', '', $controllerName);
        $modelSpaceName = $this->getModelSpaceName($className);
        
        $replace = [
            '{{ class }}' => $className,
            '{{class}}' => $className,
            '{{ namespacedModel }}' => $modelSpaceName,
            '{{ model }}' => $name,
            '{{ modelVariable }}' => lcfirst($name),
        ];

        return str_replace(array_keys($replace), array_values($replace), $stub);
    }
}
