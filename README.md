# Behamin Filter
apply auto filters in Eloquent. <br>
with this package, easily apply filters, sort and offset with limit on Eloquent Builder. <br>
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
}

```
### Create Filter Class
```
php artisan make:filter {name}
```

### Usage
In controllers 
```
public function index(YourModelFilter $filters): Response
{
    [$models, $count] = YourModel::filter($filters);
}
```