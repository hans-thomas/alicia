---
title: "Alicia handler trait"
description: "Introducing Alicia handler trait and its available methods."
lead: ""
date: 2023-06-11
lastmod: 2023-06-11
draft: false
images: []
menu:
docs:
parent: "Getting start"
weight: 120
toc: true
---

Alicia handler trait comes with relation definition and some helper methods to help you to keep your code simple.

## Available methods

<div class="methods-container">


<div class="method">

[attachments](#attachments)
</div>

<div class="method">

[attachment](#attachment)
</div>

<div class="method">

[deleteAttachments](#deleteAttachments)
</div>

<div class="method">

[attachTo](#attachTo)
</div>

<div class="method">

[attachManyTo](#attachManyTo)
</div>


</div>

### attachments

The relationship definition to `Hans\Alicia\Models\Resource` model.

```php
use Hans\Alicia\Facades\Alicia;

$model = Alicia::upload( request()->file('file') )->getData();
$post->attachments()->sync( $model );
```

### attachment

Returns the oldest attached file.

```php
use Hans\Alicia\Facades\Alicia;

$modelB = Alicia::external( 'https://www.file-server.dev/files/file-name.extension' )->getData();
$modelA = Alicia::upload( request()->file('file') )->getData();

$post->attachTo( $modelA );
$post->attachTo( $modelB );

$post->attachment(); // returns $modelA
```

### deleteAttachments

Detaches and deletes all related resources.

```php
use Hans\Alicia\Facades\Alicia;

$modelB = Alicia::external( 'https://www.file-server.dev/files/file-name.extension' )->getData();
$modelA = Alicia::upload( request()->file('file') )->getData();

$post->attachTo( $modelA );
$post->attachTo( $modelB );

$post->deleteAttachments();
```

### attachTo

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

### attachManyTo

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
