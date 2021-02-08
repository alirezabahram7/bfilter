# Behamin Filter
apply auto filters in Eloquent. <br>
with this package, easily apply filters, sort and paginatation on Eloquent models and their relations. <br>
with query string parameters. <br>    

### Installation
```
composer require behamin/bfilters
```
### Updating your Eloquent Models
Your models should use the `HasFilter` trait:  
```
use BFilters\Traits;

class MyModel extends Eloquent
{
    use HasFilter;
    
    // define fields for full text serach as "searchable" or "fillable"
    protected searchable = [ 'first_name','last_name' ] 
}

```
### Create Filter Class
```
php artisan make:filter {name}

for example: 
php artisan make:filter UserFilter


```
### In Created Filter Class
```
use Illuminate\Http\Request;

class UserFilter extends Filter
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        
        $this->relations = [
            //actual name of relation defined in original model
            'relationName1' => [ 
                'searchName1' => 'Original column1 Name in Relation table',
                'searchName2' => 'Original column2 Name in Relation table',
                'searchName3' => 'Original column3 Name in Relation table',

                //if searchName and original column name is same
                'Original column4 Name in Relation table'
            ],
          
            'posts' => [
                // in this case when you set posted_at as your filter the filter will applied on 'created_at' field of original table
                'posted_at' => 'created_at',
                'title',
                'topic',
            ],
        ];
        // you set this variabe if you want to have sum of your entries based of a specific field (f.e id here)
        $this->sumField = 'id';
    }
}
```

### Usage
In controllers 
```
public function index(YourModelFilter $filters): Response
{
    [$entries, $count, $sum] = YourModel::filter($filters);
}
```

In Request
```
filter:{
        "sort":[
                { "field": "created_at", "dir": "asc" },
                { "field": "first_name", "dir": "desc" }
         ],
         "page":{ "limit": 10, "offset": 0 },
         "filters":[
                    //(first_name LIKE '%alireza%' or last_name = '%bahram%') and (mobile LIKE '%9891%')
                    [ 
                       //use "or" for fields in same array & use "and" for fields in different array
                       {"field": "first_name", "op": "like", "value":  "alireza"},
                       {"field": "last_name", "op": "=", "value":  "bahram"}
                    ],
                    [
                        {"field": "mobile", "op": "like", "value": "9891"}
                    ],
                    [
                        //full search : search a string in fields you set in its model "searchable" or "fillable" arrays
                        {"value" : "al"}
                    ]
         ]
}
```