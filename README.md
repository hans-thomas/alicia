# Alicia

it is an uploader that has below features:

- easy-to-use
- customizable
- upload pictures and videos and files
- store external links
- HLS support
- collect file details

# Table of contents

- [configuration](#configuration)
- [installation](#installation)
- [usage](#usage)

## Configuration

- `base` : is a place that Alicia stores all files and directories.
- `temp` : the temp is an address of a temporary folder that files stores there before classification and optimization
  applies. (set `false` to disable this feature)
- `classification` : Alicia let you determine how to classify your files.
- `extensions` : you can define your allowed file extensions base on file types.
- `sizes` : specifies the valid size of files based on their types.
- `validation` : Alicia validates the files, but you can add more validation rules.
- `optimization` : optimizes pictures and videos by default, however you can enable/disable the optimization for each
  file type
- `naming` : there are several drivers to determine that how to generate files name
- `link` : you can customize download link.

> notice: you should not add or remove any parameter

- `signed` : this option creates a signed route with a hash key and an expiration time. this option prevent users
  sharing download links. (if you want a permanent link, just set this `false`)

> info: hash key will create using user's ip and user-agent

- `secret`: the string that hash key encrypts and decrypts.
- `expiration`: expiration time for signed routes.
- `attributes`: custom attributes for download link. for example, you can add a middleware.
- `onlyPublishedFiles`: files will not publish until classification and optimization jobs be done. so, if you want to
  show only published files, you need to set this option `true`.
- `hls`: you can en/disable hls and customize hls exporter's parameter.
- `export`: custom resolutions to export from images.

## Installation

1. install the package via composer:

```shell
composer require hans-thomas/alicia
```

2. publish config file

```shell
php artisan vendor:publish --tag alicia-config
```

## Usage

1. upload an image

```php
app( AliciaContract::class )->upload($inputName);
```

you can override default validation and pass your rules

```php
app( AliciaContract::class )->upload($inputName,[
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:' . (string) 1 * 1024
                ]);
```

2. get the created resource(s)

```php
app( AliciaContract::class )->upload($inputName)->getData();
```

3. export different resolutions of uploaded image

```php
app( AliciaContract::class )->upload($inputName)->export()->getData();
```

you can override export settings in config file and set your resolutions.

```php
app( AliciaContract::class )->upload($inputName)->export([960  => 540])->getData();
```

4. store an external link

```php
app( AliciaContract::class )->external($inputName)->getData();
```

you can pass your rules as second argument.

5. batch upload

if you want to upload one or more files or links, you can use

```php
app( AliciaContract::class )->batch($inputName)->getData();
```

you can pass your rules for files as second argument and for links as third argument.

6. delete a resource

```php
app( AliciaContract::class )->delete($model);
```

$model can be a resource model object or a resource id.

7. batch delete

```php
app( AliciaContract::class )->batchDelete($models);
```

$models can be a collection of resource model objects or resource ids.