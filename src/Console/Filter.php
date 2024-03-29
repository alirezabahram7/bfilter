<?php

namespace BFilters\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class Filter extends GeneratorCommand
{
    protected $signature = 'make:filter {name}';
    protected $description = 'Create a new filter class';
    protected $type = "Filter";

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/filter.php.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Http\Filters';
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the filter class.'],
        ];
    }
}
