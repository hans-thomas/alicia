---
title: "Installation"
description: "Installation guidance to add Alicia to your laravel project."
lead: ""
date: 2023-06-11
lastmod: 2023-06-11
draft: false
images: []
menu:
docs:
parent: "Getting started"
weight: 100
toc: false
---


<p><img alt="alicia banner" src="/images/alicia-banner.png"></p>

[![codecov](https://codecov.io/gh/hans-thomas/alicia/branch/master/graph/badge.svg?token=X1D6I0JLSZ)](https://codecov.io/gh/hans-thomas/alicia)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/hans-thomas/alicia/php.yml)
![GitHub top language](https://img.shields.io/github/languages/top/hans-thomas/alicia)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/hans-thomas/alicia)

## Installation

Install the package via composer

```shell
composer require hans-thomas/alicia
```

Then, publish config file using

```shell
php artisan vendor:publish --tag alicia-config
```

In the end, use `AliciaHandler` trait on your model.

```php
use Hans\Alicia\Traits\AliciaHandler;
use Illuminate\Database\Eloquent\Model;

class Post extends Model {
    use AliciaHandler;

    // ...
}
```

Done.
