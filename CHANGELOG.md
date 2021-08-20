# Changelog
Changelog documentation for the Cherrycake engine. This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0b] - 2021-08-20
### Changed
- Multilanguage texts are managed by the easier to use new `Translation` module instead of the previous `Locale` module.

## [1.0.0b] - 2021-05-25
### Changed
- Composer-based autoloading system, a standard class and module autloading mechanism that simplifies overall structure for Cherrycake apps.
- Core modules are now stored in <Engine dir>/src/<Module name>/<Module name>.php
- Core classes are now stored in <Engine dir>/src/<Class name>.php
- App module are now stored in <App dir>/src/<Module name>/<Module name>.php by default
- App classes are now stored in <App dir>/src/<Class name>.php by default
- Class and module files must now have `.php` extension instead of `.class.php`
- Module configuration files are now autodetected, so `isConfigFile` property for modules is no longer needed.
- Janitor tasks configuration files are now autodetected, so `isConfigFile` property is no longer needed.
- Global constants are declared in `/constants.php`,
### Removed
- UIComponents are no longer part of Cherrycake because they were based on an obsolete web design standard, in favor of modern web UI techniques.
