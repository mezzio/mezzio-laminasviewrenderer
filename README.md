# laminas-view PhpRenderer Integration for Mezzio

[![Build Status](https://travis-ci.org/mezzio/mezzio-laminasviewrenderer.svg?branch=master)](https://travis-ci.org/mezzio/mezzio-laminasviewrenderer)

[laminas-view PhpRenderer](https://github.com/laminas/laminas-view) integration
for [Mezzio](https://github.com/mezzio/mezzio).

## Installation

Install this library using composer:

```bash
$ composer require mezzio/mezzio-laminasviewrenderer
```

We recommend using a dependency injection container, and typehint against
[container-interop](https://github.com/container-interop/container-interop). We
can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`
- [pimple-interop](https://github.com/moufmouf/pimple-interop):
  `composer require mouf/pimple-interop`
- [Aura.Di](https://github.com/auraphp/Aura.Di)

## View Helpers

To use view helpers, the `LaminasViewRendererFactory`:

- requires a `config` service; with
- a `view_helpers` sub-key; which
- follows standard laminas-servicemanager configuration.

To use the `UrlHelper` provided in this package, ensure that you register its
factory in that configuration:

```php
use Mezzio\LaminasView\UrlHelperFactory;

return [
    'view_helpers' => [
        'factories' => [
            'url' => UrlHelperFactory::class,
        ],
    ],
];
```

## Documentation

See the [mezzio](https://github.com/mezzio/mezzio/blob/master/doc/book)
documentation tree, or browse online at http://mezzio.rtfd.org.
