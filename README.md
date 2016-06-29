# laravel-cdnify
[![GitHub release](https://img.shields.io/github/release/metrique/laravel-cdnify.svg?maxAge=2592000)]()

## Features
- Add a CDN to any path when a specified environment is active in Laravel 5.
- Easily publish elixir versioned assets with one simple command.

## Installation

`composer require metrique/laravel-cdnify`

## Usage

### Example.

#### Get Helper
A resource exists in your rev-manifest.json, which has created by Laravel Elixir.
`<script src="{{ $cdnify->get('js/site.js', true) }}" async></script>`

#### Get the CDN as a string.
`$cdnify->cdn();`

#### Set a local path and get the full CDN path.
`$cdnify->path('/some/static/resource.jpg')->toString();`

### Config

Config defaults can be configured by editing `config/cdnify.php` in your main application directory.

You can publish the  `config/cdnify.php` config file to your application config directory by running `php artisan vendor:publish --tag="cdnify-config"`

### CDNify

$cdnify is automatically registered for use in all Laravel views.

`$cdnify->defaults();` If *environments*, *elixir* or *roundRobin* settings are changed, this will discard the changes in favour of the config settings.

`$cdnify->cdn();` Returns a CDN path from the config, if roundRobin is set to true then it will iterate through the list of CDN's on each call.

`$cdnify->path($path);` Sets the path to be CDNified.

`$cdnify->toString();` Returns the CDN and path as a string.

`$cdnify->get($path, $elixir = null);` Helper utility combining the path, elixir and toString methods.

`$cdnify->environments($environments);` Set the environments where the path should be CDNified.

`$cdnify->elixir($bool);` Sets whether elixir should be used, if available.

`$cdnify->roundRobin($bool);` Enables round robin iteration on the cdn list.

### CDNify command

`php artisan metrique:cdnify`
This command will run `gulp --production` and then deploy any assets listed in rev-manifest.json to s3 (or other disk), via the Laravel Filesystem.

### Options

`--build-source[=BUILD-SOURCE]` Sets the path to the source files that are to be uploaded. [default: "/build"]

`--build-dest[=BUILD-DEST]` Sets the path where files are to be uploaded. [default: "/build"].

`--disk[=DISK]` Set disk/upload method. [default: "s3"]

`--force` Toggle force upload of files.

`--manifest[=MANIFEST]` Set manifest location. [default: "/build/rev-manifest.json"]
