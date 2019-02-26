# Laravel Repository

**Simple repository pattern for laravel**

Keep clean your models and controllers

**Installation**

Install via composer.

`composer require hsntngr/laravel-repository`

Register repository service provider by adding into providers in config/app.php
```php
'providers' => [
    // ...
    Hsntngr\Repository\ServiceProvider::class
];
```

Publish configuration file.

`php artisan vendor:publish --provider="Hsntngr\Repository\ServiceProvider::class" --tag="config"`

Then adjust your `App\Http\Controllers\Controller` class to use repository manager in all controllers. With Repository Manager you will able to instantiate any repository in anywhere

```php
// ...
use Hsntngr\Repository\IRepositoryManager;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $rm;

    public function __construct(IRepositoryManager $rm)
    {
        $this->rm = $rm;
    }
}
//Now you can easly instantiate a repository with $this->rm->{repositoryKey}
```
**Usage**

Use `make:repository Model` command to create new repository. 

```
php artisan make:repository User

// app/Repositories/UserRepository.php

// Also you may define custom alias to retrieve repository. By default it uses lower case model name 

php artisan make:repository PostTranslation --key=translate
```
Example

```php
# PostController
public function show($id)
{
    $post = $this->rm->post->findPublishedPost($id);
    
    return view("posts.single",compact("post"));
}
```
If you want to cache results use `cache() & repository()` methods
```php
public function show($id)
{
    $post = $this->rm->cache("post:".$id,3*60)
         ->repository("post",function($post) use ($id){
             return $post->findPublishedPost($id);
         });
    // if no minutes defined, it'll cache forever
    return view("posts.single",compact("post"));
}
```
Use `repository()` helper to instantiate any repository outside of the controller
```php
repository("post")->findPublishedPost($id);
```
To list repositories, use `repository:list` command
```
php artisan repository:list

// post           ->  PostRepository
// user           ->  UserRepository
// comment        ->  CommentRepository
```