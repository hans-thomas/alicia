---
title: "Facade"
description: "Introducing Alicia facade and its available methods."
lead: ""
date: 2023-06-11
lastmod: 2023-06-11
draft: false
images: []
menu:
docs:
parent: "Getting started"
weight: 110
toc: true
---

Alicia facade helps to use this package easily and faster. this facade contains a bunch of methods that in the next, we
will introduce them.

## Available methods

<div class="methods-container">


<div class="method">

[batch](#batch)
</div>

<div class="method">

[upload](#upload)
</div>

<div class="method">

[external](#external)
</div>

<div class="method">

[export](#export)
</div>

<div class="method">

[delete](#delete)
</div>

<div class="method">

[batchDelete](#batchDelete)
</div>

<div class="method">

[deleteFile](#deleteFile)
</div>

<div class="method">

[makeExternal](#makeExternal)
</div>

<div class="method">

[fromFile](#fromFile)
</div>

<div class="method">

[getData](#getData)
</div>


</div>


### batch

Uploads and stores files and links at once.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::batch( request()->input('files') )->getData();
```

> `files` input can contain files and links.

### upload

Uploads a single file on the server.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::upload( request()->file('file') )->getData();
```

### external

Stores an external link on the database.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::external( request()->input('link') )->getData();
```

### export

Creates different versions of uploaded image.

```php
use Hans\Alicia\Facades\Alicia;

Alicia::upload( request()->file('file') )->export()->getData();
```

### delete

Delete a model instance and its related files.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();

Alicia::delete($model);
```

### batchDelete

Delete several models and their related files at once.

```php
use Hans\Alicia\Facades\Alicia;

$modelA = Alicia::upload( request()->file('file') )->getData();
$modelB = Alicia::upload( request()->file('file') )->getData();

Alicia::batchDelete( [$modelA,$modelB->id] );
```

### makeExternal

Sometimes you need to move your file to another server and access it using a link.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();
// Upload given file on another server and get its link
$link = 'https://www.file-server.dev/files/file-name.extension';

Alicia::makeExternal( $model,$link );
```

> This make it easy to move your files to another server without doing heavy updates or re-creating you resources.

### fromFile

If you have your file on the server and want to store it on database, you can use `fromFile` method.

```php
use Hans\Alicia\Facades\Alicia;

$file = '/path/to/file.extension';

$model = Alicia::fromFile( $file )->getData();
```

### getData

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
