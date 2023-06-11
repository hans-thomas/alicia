# Alicia

It's a file uploader and manager with below features:

- Upload pictures and videos and files
- Store external links
- HLS support
- Collect file's detail
- Classification for files

## Installation

Install the package via composer

```shell
composer require hans-thomas/alicia
```

Then, publish config file using

```shell
php artisan vendor:publish --tag alicia-config
```

In the end, use `AliciaHandler` trait to your model.

```php
use Hans\Alicia\Traits\AliciaHandler;
use Illuminate\Database\Eloquent\Model;

class Post extends Model {
    use AliciaHandler;
    
    // ...
}
```

## Usage

### Alicia facade

#### batch

Uploads and stores files and links at once.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::batch( request()->input('files') )->getData();
```

> `files` input can contain files and links.

#### upload

Uploads a single file on the server.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::upload( request()->file('file') )->getData();
```

#### external

Stores an external link on the database.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::external( request()->input('link') )->getData();
```

#### export

Creates different versions of uploaded image.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::upload( request()->file('file') )->export()->getData();
```

#### delete

Delete a model instance and its related files.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();

Alicia::delete($model);
```

#### batchDelete

Delete several models and their related files at once.

```php
use Hans\Alicia\Facades\Alicia;

$modelA = Alicia::upload( request()->file('file') )->getData();
$modelB = Alicia::upload( request()->file('file') )->getData();

Alicia::batchDelete( [$modelA,$modelB->id] );
```

#### makeExternal

Sometimes you need to move your file to another server and access it using a link.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();
// Upload given file on another server and get its link
$link = 'https://www.file-server.dev/files/file-name.extension';

Alicia::makeExternal( $model,$link );
```

> This make it easy to move your files to another server without doing heavy updates or re-creating you resources.

#### fromFile

If you have your file on the server and want to store it on database, you can use `fromFile` method.

```php
use Hans\Alicia\Facades\Alicia;

$file = '/path/to/file.extension';

$model = Alicia::fromFile( $file )->getData();
```

#### getData

After you create a resource, you can call `getData` method to get your created resources. if your action created one
model instance, this method returns a single model instance. for instance, when we upload a single file.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::upload( request()->file('file') )->getData();
```

But, if we call `export` method after uploading an image or using `batch` method, the created models could be more than
one instance, so `getData` method returns a collection of created models.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::upload( request()->file('file') )->export()->getData();
```

### AliciaHandler

This trait contains relationships with resource model and a bunch of helper methods.

#### attachments

The relationship definition to `Hans\Alicia\Models\Resource` model.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();
$post->attachments()->sync( $model );
```

#### attachment

Returns the oldest attached file.

```php
use Hans\Alicia\Facades\Alicia;

$modelB = Alicia::external( 'https://www.file-server.dev/files/file-name.extension' )->getData();
$modelA = Alicia::upload( request()->file('file') )->getData();

$post->attachTo( $modelA );
$post->attachTo( $modelB );

$post->attachment(); // returns $modelA
```

#### deleteAttachments

Detaches and deletes all related resources.

```php
use Hans\Alicia\Facades\Alicia;

$modelB = Alicia::external( 'https://www.file-server.dev/files/file-name.extension' )->getData();
$modelA = Alicia::upload( request()->file('file') )->getData();

$post->attachTo( $modelA );
$post->attachTo( $modelB );

$post->deleteAttachments();
```

#### attachTo

Using this method, you can attach a resource to your model instance.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();

$post->attachTo( $model );
```

By the way, you can set a key for your attachments.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();

$post->attachTo( $model,'avatar' );
```

#### attachManyTo

It's like `attachTo` method but, you can attach multiple resources at once.

```php
use Hans\Alicia\Facades\Alicia;

$modelB = Alicia::external( 'https://www.file-server.dev/files/file-name.extension' )->getData();
$modelA = Alicia::upload( request()->file('file') )->getData();

$post->attachManyTo( [
    $modelA->id,
    $modelB->id => [ 'key' => $key = 'avatar' ],
] );
```

Support
-------

- [Report bugs](https://github.com/hans-thomas/alicia/issues)

