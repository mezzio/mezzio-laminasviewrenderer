# laminas-view PhpRenderer Integration for Mezzio

[![Build Status](https://travis-ci.org/mezzio/mezzio-laminasviewrenderer.svg?branch=master)](https://travis-ci.org/mezzio/mezzio-laminasviewrenderer)
[![Coverage Status](https://coveralls.io/repos/mezzio/mezzio-laminasviewrenderer/badge.svg?branch=master)](https://coveralls.io/r/mezzio/mezzio-laminasviewrenderer?branch=master)

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

## Documentation

Browse online at https://docs.mezzio.dev/mezzio/features/template/laminas-view/.
