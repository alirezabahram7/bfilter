<?php


namespace Behamin\Bfilters\Console;


use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class Filter extends GeneratorCommand
{
    protected $signature = 'make:filter {name}';

    protected function getStub()
    {
        return __DIR__ . '\stubs\filter.php.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Filters';
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the contract.'],
        ];
    }
}
