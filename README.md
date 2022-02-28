# Alicia

is a video and picture uploader that has belows features:

- easy-to-use
- customizable
- upload pictures and videos and files
- store external links
- HLS support
- collect file's details

## configuration

- `base folder` : is a place that Elurra stores all files
- `temp folder` : the temp folder is a temporary folder that files stores before classification and optimization
- `classification` : Elurra let you to determine how to classify your files
- `extensions` : you can define your allowed file extensions base on files type
- `sizes` : you can specify the allowed file size based on files type
- `validation` : Elurra validate the inputs, but you can add some more validations rule
- `optimization` : Elurra optimizes pictures and videos by default, however you can enable/disable the optimization for
  each file's type

- `keys` : specify the keys that exported from getModel method. for getting the full model object just set keys to false

- `naming` : there are several drivers to determine that how to generate files name

## Installation

install via composer
> composer require hans-thomas/elurra

and publish the config file using this command
> php artisan vendor:publish --tag=elurra-config

then create your base folder according to Elurra's config file and give needed permission to it.
> `chown -R www-data:www-data baseFolder/` for docker in docker

also, there are some tools that Elurra uses like : `ffmpeg`, `jpegoptim`, `pngquant`, `webp`

you can install them using this command on your dockerfile

```@shell
RUN apt-get install -y \
    ffmpeg \
    jpegoptim \
    pngquant \
    webp
```

## Relationship

to define needed relationship for your models that has uploaded files, just add `Hans\Elurra\Traits\ElurraRelationship`
trait to your models, then you can access related files on your model instance using `uploads` MorphToMany relation.

## Usage

### Upload

to upload a file you can use

```@php
Elurra::upload( 'file' )
```

to store a external links

```@php
Elurra::external( 'link' )
```

and in the end, you can upload both files and links using

```@php
Elurra::batch('files')
```

> notice: for batch upload you need to define your inputs name as array

after uploading the file(s) you can get related model or id like this:

```@php
Elurra::upload( 'file' )->getModel()
```

for bath uploads use :

```@php
Elurra::batch('files')->getModels()
```

in the other hand, if you just want the id(s) to sync with your model you can do this

```@php
YourModel::find(1)->uploads()->sync( Elurra::upload( 'file' )->getId() );
```

and for batch uploads

```@php
YourModel::find(1)->uploads()->sync( Elurra::batch( 'files' )->getIds() );
```

### Delete

to delete a file just pass the file's id

```@php
Elurra::delete( $Id )
```

to delete a group of files, just pass the file's ids as array

```@php
Elurra::batchDelete( $Ids )
```
