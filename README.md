# A WebDAV server implementation for Laravel based on SabreDAV, with support for Laravel filesystem disks.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Total Downloads](https://img.shields.io/packagist/dt/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Latest Unstable Version](https://poser.pugx.org/n3xt0r/laravel-webdav-server/v/unstable)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-webdav-server.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-webdav-server)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can
support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.
You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards
on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require n3xt0r/laravel-webdav-server
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-webdav-server-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-webdav-server-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-webdav-server-views"
```

## Usage

```php
$laravelWebdavServer = new N3XT0R\LaravelWebdavServer();
echo $laravelWebdavServer->echoPhrase('Hello, N3XT0R!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ilya Beliaev](https://github.com/N3XT0R)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
