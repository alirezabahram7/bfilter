<?php

namespace BFilters\Tests;

use BFilters\Filter;
use Orchestra\Testbench\Concerns\CreatesApplication;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    public string $filterCompleteCasesJson;
    public Filter $filter;

    /**
     * @throws \JsonException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $request = \request();
        $request->merge(['filter' => $this->filterCompleteCasesJson]);
        $this->filter = new Filter($request);

    }
}
