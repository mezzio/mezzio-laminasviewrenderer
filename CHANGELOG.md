# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.4.1 - 2017-12-12

### Added

- [zendframework/zend-expressive-zendviewrenderer#39](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/39)
  adds the ability for the `LaminasViewRendererFactory` to use the
  `Laminas\View\Renderer\PhpRenderer` service when present, defaulting to creating
  an unconfigured instance if none is available (previous behavior).

### Changed

- [zendframework/zend-expressive-zendviewrenderer#41](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/41)
  updates the renderer to also inject the layout with any default parameters (vs
  only the template requested).

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#43](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/43)
  ensures that if a view model provided to the renderer contains child view
  models, then it will properly merge variables pulled from the child model.
  Previously, an error would occur due to an attempt to merge either a null or
  an object where it expected an array.

## 1.4.0 - 2017-03-14

### Added

- [zendframework/zend-expressive-zendviewrenderer#36](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/36)
  adds support for mezzio-helpers 4.0.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.0 - 2017-03-02

### Added

- [zendframework/zend-expressive-zendviewrenderer#23](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/23)
  adds the ability to disable layouts either globally or when rendering. Disable
  globally by setting the default `layout` parameter to boolean `false`:

  ```php
  $renderer->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'layout', false);
  ```

  Or do so when rendering, by passing the template variable `layout` with a
  boolean `false` value:

  ```php
  $renderer->render($templateName, [
      'layout' => false,
      // other template variables
  ]);
  ```

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - 2017-01-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#33](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/33)
  fixes the signature of the `UrlHelper` to make the default value of
  `$fragmentIdentifer` a `null` instead of `''`; this fixes an issue whereby
  missing fragments led to exceptions thrown by mezzio-helpers.

## 1.2.0 - 2017-01-11

### Added

- [zendframework/zend-expressive-zendviewrenderer#30](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/30)
  adds support for mezzio-router 2.0.

- [zendframework/zend-expressive-zendviewrenderer#30](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/30)
  adds support for mezzio-helpers 2.2 and 3.0.

- [zendframework/zend-expressive-zendviewrenderer#30](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/30)
  adds new arguments to the `url()` helper:

  ```php
  echo $this->url(
      $routeName,   // (optional) string route for which to generate URI; uses matched when absent
      $routeParams, // (optional) array route parameter substitutions; uses matched when absent
      $queryParams, // (optional) array query string arguments to include
      $fragment,    // (optional) string URI fragment to include
      $options,     // (optional) array of router options. The key `router` can
                    //     contain options to pass to the router; the key
                    //     `reuse_result_params` can be used to disable re-use of
                    //     matched routing parameters.
  );
  ```

  If using mezzio-router versions prior to 2.0 and/or
  mezzio-helpers versions prior to 3.0, arguments after `$routeParams`
  will be ignored.

### Changed

- [zendframework/zend-expressive-zendviewrenderer#26](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/26)
  updated the laminas-view dependency to 2.8.1+.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-zendviewrenderer#26](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/26)
  removes the dependencies for the laminas-i18n and laminas-filter packages, as they
  are no longer required by the minimum version of laminas-view supported.

  If you depended on features of these, you may need to re-add them to your
  application:

  ```bash
  $ composer require laminas/laminas-filter zendframework/zend-i18n
  ```

- This release removes support for PHP 5.5.

### Fixed

- Nothing.

## 1.1.0 - 2016-03-23

### Added

- [zendframework/zend-expressive-zendviewrenderer#22](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/22)
  adds support for the laminas-eventmanager and laminas-servicemanager v3 releases.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.1 - 2016-01-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#19](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/19)
  updates the mezzio-helpers dependency to `^1.1 || ^2.0`, allowing it
  to work with either version.

## 1.0.0 - 2015-12-07

First stable release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.4.1 - 2015-12-06

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#14](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/14)
  updates the mezzio-helpers dependency to `^1.1`, allowing removal of
  the mezzio development dependency.

## 0.4.0 - 2015-12-04

### Added

- [zendframework/zend-expressive-zendviewrenderer#11](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/11)
  adds a factory for providing the `HelperPluginManager`, and support in the
  `LaminasViewRendererFactory` for injecting the `HelperPluginManager` service
  (using its FQCN) instead of instantiating one directly. 
- [zendframework/zend-expressive-zendviewrenderer#13](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/13)
  adds `zendframework/zend-expressive-helpers` as a dependency, in order to
  consume its `UrlHelper` and `ServerUrlHelper` implementations.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-zendviewrenderer#13](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/13)
  removes the `UrlHelperFactory`.
- [zendframework/zend-expressive-zendviewrenderer#13](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/13)
  removes the `Mezzio\LaminasView\ApplicationUrlDelegatorFactory`. This
  functionality is obsolete due to the changes made to the `UrlHelper` in this
  release.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#13](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/13)
  updates the `UrlHelper` to be a proxy to `Mezzio\Helper\UrlHelper`.
- [zendframework/zend-expressive-zendviewrenderer#13](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/13)
  updates the `ServerUrlHelper` to be a proxy to `Mezzio\Helper\ServerUrlHelper`.
- [zendframework/zend-expressive-zendviewrenderer#13](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/13)
  modifies the logic for injecting the `url` and `serverurl` helpers to pull the
  `Mezzio\Helper\UrlHelper` and `Mezzio\Helper\ServerUrlHelper`
  instances, respectively, to inject into the package's own `UrlHelper` and
  `ServerUrlHelper` instances.

## 0.3.1 - 2015-12-03

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendviewrenderer#12](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/12)
  updates the `UrlHelper` to implement the `Mezzio\RouteResultObserverInterface`
  from the mezzio/mezzio package, instead of
  `Mezzio\Router\RouteResultObserverInterface` from the
  mezzio/mezzio-router package (as it is now
  [deprecated](https://github.com/zendframework/zend-expressive-router/pull/3).

## 0.3.0 - 2015-12-02

### Added

- [zendframework/zend-expressive-zendviewrenderer#4](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/4)
  Allow rendering view models via render
- [zendframework/zend-expressive-zendviewrenderer#9](https://github.com/zendframework/zend-expressive-zendviewrenderer/pull/9)
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
