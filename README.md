# laravel-cdnify

## Features
- Add a CDN to any path when a specified environment is active in Laravel 5.
- Easily publish elixir versioned assets with one simple command.

## Installation


```
"repositories": [
    {
        "url": "https://github.com/Metrique/laravel-cdnify",
        "type": "git"
    }
],
```

1. Add the above to the repositories section of your composer.json
2. Add `"Metrique/laravel-cdnify": "dev-master"` to the require section of your composer.json.
3. Add `Metrique\CDNify\CDNifyServiceProvider::class,` to your list of service providers. in `config/app.php`.
4. `composer update`.

## Usage

### Config

Config defaults can be configured by editing `config/cdnify.php` in your main application directory.

You can publish the  `config/cdnify.php` config file to your application config directory by running `php artisan vendor:publish`

### CDNify

$cdnify is automatically registered for use in all Laravel views.

`$cdnify->defaults();` Set the settings back to the config defaults.

`$cdnify->get($path, $elixir = true);` Helper utility combining the path, elixir and toString methods.

`$cdnify->toString();` Returns the CDN path as a string.

`$cdnify->cdn();` Returns a CDN path, if roundRobin is set to true then it will roundRobin the list of CDN's

`$cdnify->path($path);` Set the path to be CDNified.

`$cdnify->environments($environments);` Set the environments where the path should be CDNified, if null defaults will be used.

`$cdnify->elixir($bool);` Set whether elixir should be used if available.

`$cdnify->roundRobin($bool);` Enables round robin on the cdn list.

### CDNify command
```
php artisan metrique:cdnify
```
This will deploy any assets listed in build-revision.json to s3, via the Laravel Filesystem.
### Options

`--build-source[=BUILD-SOURCE]` Set build path. [default: "/build"]

`--build-dest[=BUILD-DEST]` Set build path.

`--disk[=DISK]` Set disk/upload method. [default: "s3"]

`--force` Toggle force upload of files.

`--manifest[=MANIFEST]` Set manifest location. [default: "/build/rev-manifest.json"]
