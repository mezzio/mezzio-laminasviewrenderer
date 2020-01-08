# laminas-view PhpRenderer Integration for Mezzio

[![Build Status](https://travis-ci.com/mezzio/mezzio-laminasviewrenderer.svg?branch=master)](https://travis-ci.com/mezzio/mezzio-laminasviewrenderer)
[![Coverage Status](https://coveralls.io/repos/github/mezzio/mezzio-laminasviewrenderer/badge.svg?branch=master)](https://coveralls.io/github/mezzio/mezzio-laminasviewrenderer?branch=master)

[laminas-view PhpRenderer](https://github.com/laminas/laminas-view) integration
for [Mezzio](https://github.com/mezzio/mezzio).

## Installation

Install this library using composer:

```bash
$ composer require mezzio/mezzio-laminasviewrenderer
```

We recommend using [PSR-11](https://www.php-fig.org/psr/psr-11/) compatible
dependency injection container. We can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`
- [Pimple](https://github.com/silexphp/Pimple):
  `composer require laminas/laminas-pimple-config`
- [Aura.Di](https://github.com/auraphp/Aura.Di):
  `composer require laminas/laminas-auradi-config`

## View Helpers

To use view helpers, the `LaminasViewRendererFactory`:

- requires a `config` service; with
- a `view_helpers` sub-key; which
- follows standard laminas-servicemanager configuration.

## Documentation

Browse online at https://docs.mezzio.dev/mezzio/features/template/laminas-view/.
