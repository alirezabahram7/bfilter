<?php


namespace BFilters\Tests;


use Tests\CreatesApplication;

class RemoveFilterTest extends TestCase
{
    use CreatesApplication;
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
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testRemoveTwoFilters(){
        $this->filter->removeFilter('mobile')->removeFilter('last_name');
        $filters = $this->filter->getFilters();
        $this->assertCount(1, $filters);
    }
}