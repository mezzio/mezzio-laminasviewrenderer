# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.3.0 - 2015-12-02

### Added

- [zendframework/zend-expressive-zendviewrenderer#4](https://github.com/mezzio/mezzio-laminasviewrenderer/pull/)
  Allow rendering view models via render
- [zendframework/zend-expressive-zendviewrenderer#9](https://github.com/mezzio/mezzio-laminasviewrenderer/pull/)
  updates `UrlHelper` to implement `Mezzio\Template\RouteResultObserverInterface`,
  and the `update()` method it defines. This allows it to observer the
  application for the `RouteResult` and store it for later URI generation.
  To accomplish this, the following additional changes were made:
  - `Mezzio\LaminasView\UrlHelperFactory`  was added, for creating the
    `UrlHelper` instance. This should be registered with the application service
    container.
  - `Mezzio\LaminasView\LaminasViewRendererFactory` was updated to look for
    the `Mezzio\LaminasView\UrlHelper` service in the application service
    container, and use it to seed the `HelperManager` when available.
  - `Mezzio\LaminasView\ApplicationUrlDelegatorFactory` was created; when
    registered as a delegator factory with the `Mezzio\Application`
    service, it will pull the `UrlHelper` and attach it as a route result
    observer to the `Application` instance. Documentation was also provided for
    creating a Pimple extension for accomplishing this.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#6](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/6)
  Merge route result params with those provided
- [zendframework/zend-expressive-zendviewrenderer#10](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/10)
  updates the code to depend on [mezzio/mezzio-template](https://github.com/mezzio/mezzio-template)
  and [mezzio/mezzio-router](https://github.com/mezzio/mezzio-router)
  instead of zendframework/zend-expressive.

## 0.2.0 - 2015-10-20

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Update to zend-expressive RC1.
- Added branch alias of dev-master to 1.0-dev.

## 0.1.2 - 2015-10-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#1](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/1)
  adds a dependency on laminas/laminas-i18n, as it's required for use of the
  PhpRenderer.

## 0.1.1 - 2015-10-10

Initial release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to mezzio `^0.5`

## 0.1.0 - 2015-10-10

Initial release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
