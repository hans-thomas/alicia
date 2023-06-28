# Alicia

[![codecov](https://codecov.io/gh/hans-thomas/alicia/branch/master/graph/badge.svg?token=X1D6I0JLSZ)](https://codecov.io/gh/hans-thomas/alicia)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/hans-thomas/alicia/php.yml)
![GitHub top language](https://img.shields.io/github/languages/top/hans-thomas/alicia)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/hans-thomas/alicia)

It's a file uploader and manager with below features:

- Upload pictures and videos and files
- Store external links
- HLS support
- Collect file's detail
- Classification for files
- Automatic optimization

for more information [see documentation](https://docs-alicia.vercel.app/).

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

Support
-------

- [Documentation](https://docs-alicia.vercel.app/)
- [Report bugs](https://github.com/hans-thomas/alicia/issues)

