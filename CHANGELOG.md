# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Changed

- **BC:** `Rareloop\Lumberjack\Application` no longer implements `Interop\Container\ContainerInterface`. The interface was deprecated upstream and dropped by `php-di/php-di` 7. Migration: any consumer code type-hinting `Interop\Container\ContainerInterface` against `Application` should switch to `Psr\Container\ContainerInterface`.
- **BC:** Removed `blast/facades` dependency (last updated ~10 years ago, pulled in abandoned `container-interop/container-interop`). Replaced with an in-tree minimal implementation matching upstream Rareloop/lumberjack-core PR #61: new `Rareloop\Lumberjack\FacadeFactory` class and `Rareloop\Lumberjack\Facades\AbstractFacade` base class. The static API on existing facades (`Config::get()`, `Log::debug()`, etc.) is unchanged. Migration: any consumer code that imported `Blast\Facades\AbstractFacade` or `Blast\Facades\FacadeFactory` should change the namespace to `Rareloop\Lumberjack\Facades\AbstractFacade` and `Rareloop\Lumberjack\FacadeFactory` respectively. Method names unchanged.
- Bumped `php-di/php-di` constraint from `^6.3.5` to `^7.0` for PHP 8.4 deprecation cleanliness. `ContainerBuilder::buildDevContainer()` was removed in PHP-DI 7; replaced internally with `new Container()`.
- Replaced abandoned `tightenco/collect` with `illuminate/collections` for PHP 8.4 deprecation cleanliness. Drop-in for code already importing from `Illuminate\Support`.

### Fixed

- Fixed PHP 8.4 "implicitly marking parameter as nullable is deprecated" warnings across 9 method signatures: `Application::shutdown`, `AbstractController::addFlash`, `AssetExtension::getAssetUrl/getAssetVersion`, `IndentMiddleware::__construct`, `ArrayLoader::__construct/load/supports`, `RedirectableCompiledUrlMatcher::redirect`. No behavior change.
- Added `: bool` return type to `Application::has()` to satisfy stricter `Psr\Container\ContainerInterface` v2.0 LSP contract (now required transitively via PHP-DI 7).

## 5.0.0

### Changed

- Upgraded the deprecated `zendframework/zend-diactoros` to the new `laminas/laminas-diactoros` package 

## 4.4.0

### Added

- Add session garbage collection

## 4.3.2

### Patched

- Improved log formatting

## 4.3.1

### Patched

- Switched to the Statamic fork of `Stringy` to fully support PHP 7.4

## 4.3.0

### Added

- Support Middleware Aliases on Routes & Controllers
- `Helpers::logger()` (and global `logger()`) helper functions
- Bound the `Logger` instance to the PSR-3 interface `Psr\Log\LoggerInterface` in the Container.

### Patched

- Prevent Errors with a level of `E_USER_NOTICE` or `E_USER_DEPRECATED` from being fatal.

## 4.2.0

### Added

- Macroable support to `QueryBuilder`
- Add `first()` method to `QueryBuilder`
- Allow middleware to be added within a controller, including WordPress controllers
- Add `has()` method to `Config`

### Patched

- Ensure `get()` and `first()` on the `QueryBuilder` return consistent responses

## 4.1.0

### Added

- Macroable support to `Router` and `Post`

## 4.0.0

## 3.3.1 (2018-08-01)

### Patched

- Add Zend Diactoros as a direct package dependency
- Remove unused `use` statements across the codebase

## 3.3.0 (2018-07-17)

### Added

- Add `runningInConsole()` function to `Application`
- Add `mergeConfigFrom()` function to `ServiceProvider`
- Add warning to log when no WP Controller is found

## 3.2.1 (2018-05-27)

### Patched

- Prevent duplicate headers being sent

## 3.2.0 (2018-05-10)

### Added

- Add `Responsable` interface which can be used as a return object in Controllers or added to Exceptions and automatically handled by the application.
- Add `Helpers` class with the following functions `app()`, `config()`, `view()`, `route()` & `redirect()`. These can be added to the global namespace by including the `src/functions.php` file.

## 3.1.0 (2018-03-28)

### Added

- Add support for view models

## 3.0.0 (2018-03-23)
- Initial release. Starting at v3 to keep inline with Lumberjack theme version
