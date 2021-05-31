# Changelog
Changelog documentation for the Cherrycake engine. This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0b] - 2021-05-29
### Changed
- Composer-based autoloading system, a standard class and module autloading mechanism that simplifies overall structure for Cherrycake apps.
- Core modules are now stored in <Engine dir>/src/<Module name>/<Module name>.php
- Core classes are now stored in <Engine dir>/src/<Class name>.php
- App module are now stored in <App dir>/src/<Module name>/<Module name>.php by default
- App classes are now stored in <App dir>/src/<Class name>.php by default
- Modules are now set in their own subnamespace inside the `Cherrycake` namespace. For example, the `Actions` module now resides in the `\Cherrycake\Actions` namespace and the `Output` module now resides in the `\Cherrycake\Output` namespace.
- Because modules now reside in their own subnamespace, classes related to specific modules also reside now in the matching subnamespace. For example, the `Action`, `ActionHtml`, `Request`, `RequestPathComponent` and alike all now reside in the `\Cherrycake\Actions` namespace.
- Class and module files must now have `.php` extension instead of `.class.php`
- Module configuration files are now autodetected, so `isConfigFile` property for modules is no longer needed.
- Janitor tasks configuration files are now autodetected, so `isConfigFile` property is no longer needed.
- Global constants are declared in `/constants.php`.
- Autoloading of classes is now handled via composer, so you need to add this to your `composer.json` file:
```json
"autoload": {
	"psr-4": {
		"CherrycakeApp\\": "src/"
	}
},
```
### Removed
- UIComponents are no longer part of Cherrycake because they were based on an obsolete web design standard, in favor of modern web UI techniques.

### Migrating from Cherrycake version 0.x to Cherrycake version 1.0
- Update your `composer.json` file to require Cherrycake version 1.x instead of version 0.x:
	```bash
	composer update
	```
- Create the `src` directory in your project and move your modules there. Remember modules still have their own subdirectory under `src`. You can remove the now empty `Modules` directory.
- Move all your classes to the `src` directory. Remember classes do not have their own subdirectory, so they reside on the root of `src`. You can remove the now empty `Classes` directory.
- Rename all your modules and class files so they end with `.php` instead of `.class.php`. For example: `MyModule.php` instead of `MyModule.class.php`.
- Assign all your modules to their own namespace by modifying or adding a `namespace` directive at the top of the file. For example, if your module is called `MyModule`, you should add this at the top of `src/MyModule/MyModule.php`:
	```php
	namespace \CherrycakeApp\MyModule;
	```
- Remember also to correctly namespace the class your modules extend from. For example, instead of your module being declared like this:
	```php
	class MyModule extends Module {
	```
	declare it like this instead:
	```php
	class MyModule extends \Cherrycake\Module {
	```
- Assign all your classes the right namespace. If they're classes related to a module, move them to the related module's directory and add the matching namespace. For example, if your class is called `ClassForMyModule` and is related to a module called `MyModule`, move it to `src/MyModule`and add this at the top of `src/MyModule/ClassForMyModule.php`:
	```php
	namespace \CherrycakeApp\MyModule;
	```
- You'll need to change how you reference Cherrycake's core modules and classes throughout your code. For example, the following code:
	```php
	$e->Actions->mapAction(
		"homePage",
		new \Cherrycake\Action([
			"moduleType" => ACTION_MODULE_TYPE_APP,
			"moduleName" => "Home",
			"methodName" => "homePage",
			"request" => new \Cherrycake\Request([
				"pathComponents" => false,
				"parameters" => false
			])
		])
	);
	```
	Should be changed to this:
	```php
	$e->Actions->mapAction(
		"homePage",
		new \Cherrycake\Actions\Action([
			"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_APP,
			"moduleName" => "Home",
			"methodName" => "homePage",
			"request" => new \Cherrycake\Actions\Request([
				"pathComponents" => false,
				"parameters" => false
			])
		])
	);
	```
- Autoloading of classes is now handled via composer, so you need to add this to your `composer.json` file:
	```json
	"autoload": {
		"psr-4": {
			"CherrycakeApp\\": "src/"
		}
	}
	```
- Update composer's autoload by running the command:
	```bash
	composer dump-autoload
	```
- See the documentation at [cherrycake.io](https://cherrycake.io) and the examples at [documentation-examples.cherrycake.io/](https://documentation-examples.cherrycake.io/) to see examples using this new namespacing.
