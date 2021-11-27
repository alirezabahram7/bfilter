<?php


namespace BFilters\Tests;



class RemoveFilterTest extends TestCase
{

    protected function setUp(): void
    {
        $this->filterCompleteCasesJson = '{
          "filters": [
            [
              {
                "field": "first_name",
                "op": "like",
                "value": "yourname"
              }
            ],
            [
              {
                "field": "last_name",
                "op": "like",
                "value": "your_lastname"
              }
            ],
            [
              {
                "field": "mobile",
                "op": "like",
                "value": "09111111111"
              }
            ]
          ]
        }';
        parent::setUp();
    }

    public function test_remove_two_filters(): void{
        $this->filter->removeFilter('mobile')->removeFilter('last_name');
        $filters = $this->filter->getFilters();
        $this->assertCount(1, $filters);
    }
}