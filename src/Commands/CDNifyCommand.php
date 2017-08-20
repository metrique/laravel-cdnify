<?php

namespace Metrique\CDNify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Metrique\CDNify\Contracts\CDNifyRepositoryInterface;

class CDNifyCommand extends Command
{
    const CONSOLE_INFO = 0;
    const CONSOLE_ERROR = 1;
    const CONSOLE_COMMENT = 2;

    protected $config;

    /**
     * Counters
     */
    protected $counts = [
        'skipped' => 0,
        'uploaded' => 0,
    ];
    
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'metrique:cdnify
                            {--source : Set build source path.}
                            {--dest : Set build dest path.}
                            {--disk : Set disk/upload method.}
                            {--force : Toggle force upload of files.}
                            {--manifest : Set manifest location.}
                            {--skip-build : Skip the build step.}
                            {--detail : Show upload detail.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy laravel-mix versioned assets.';

    /**
     * Set disk/upload method.
     *
     * @var string
     */
    protected $disk = 's3';

    /**
     * List of valid disk drivers.
     *
     * @var array
     */
    protected $validDisks = [
        'local',
        's3',
        'rackspace',
    ];

    /**
     * The build source path.
     *
     * @var string
     */
    protected $build_source;

    /**
     * The build dest path.
     *
     * @var string
     */
    protected $build_dest;

    /**
     * Force reuploading of files.
     *
     * @var bool
     */
    protected $force;

    /**
     * The manifest files to use.
     *
     * @var array
     */
    protected $manifest;
    
    /**
     * Detailed output
     *
     * @var bool
     */
    protected $detail;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->cdnify = resolve(CDNifyRepositoryInterface::class);
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->setDefaults();
        $this->setOptions();

        $this->newline();
        $this->comment('metrique/laravel-cdnify');

        if ($this->confirmJob()) {
            try {
                // 1. Compile, copy and version assets.
                $this->build();

                // 2. Load newly created manifest file and parse ready for asset upload!
                $this->manifest();

                // 3. Upload the files.
                $this->upload();
            } catch (\Exception $e) {
                $this->error('Encountered an unknown error processing this request, please try again.');
            }
        }

        $this->newline();
        $this->info("Finished...");
    }

    protected function confirmJob()
    {
        $job = sprintf(
            'This will upload assets from %s to your chosen data store (%s)...',
            $this->manifest,
            $this->disk
        );

        if (!$this->skip_build) {
            $job = sprintf(
                'This will compile and upload assets from %s to your chosen data store (%s)...',
                $this->manifest,
                $this->disk
            );
        }

        $this->info($job);

        return $this->confirm('Do you wish to continue?');
    }
    /**
     * Loads defaults from the config file.
     */
    private function setDefaults()
    {
        $this->build_source = config('cdnify.command.build_source', '/');
        $this->build_dest = config('cdnify.command.build_dest', '');
        $this->detail = false;
        $this->disk = config('cdnify.command.disk', 's3');
        $this->force = config('cdnify.command.force', false);
        $this->skip_build = config('cdnify.command.skip_build', false);
        $this->manifest = config('cdnify.command.manifest', '/build/rev-manifest.json');
    }

    /**
     * Parses the command line options.
     */
    private function setOptions()
    {
        // Build
        if (is_string($this->option('source'))) {
            $this->build_source = $this->option('source');
        }

        if (is_string($this->option('dest'))) {
            $this->build_dest = $this->option('dest');
        }

        // Disk
        if (is_string($this->option('disk'))) {
            $this->disk = $this->option('disk');
        }

        if (!in_array($this->disk, $this->validDisks)) {
            $this->error('Disk not supported.');
            throw new \Exception('Disk not supported, aborting!', 1);
        }

        // Force
        if (is_bool($this->option('force'))) {
            $this->force = $this->option('force');
        }

        // Skip build
        if (is_bool($this->option('skip-build'))) {
            $this->skip_build = $this->option('skip-build');
        }

        // Manifest
        if (is_string($this->option('manifest'))) {
            $this->manifest = $this->option('manifest');
        }
        
        // Detail
        if (is_bool($this->option('detail'))) {
            $this->detail = $this->option('detail');
        }
    }

    /**
     * Runs Elixir or Gulp in production mode.
     */
    private function build()
    {
        if ($this->skip_build) {
            return false;
        }
        
        if (function_exists('mix')) {
            return $this->system('npm run production');
        }
        
        if (function_exists('elixir')) {
            return $this->system('gulp --production');
        }
    }

    /**
     * Reads the manifest file.
     */
    private function manifest()
    {
        $manifestFile = public_path().$this->manifest;

        if (file_exists($manifestFile)) {
            $this->manifest = $this->isValidJson(file_get_contents($manifestFile));
        }
    }

    /**
     * Transmits assets included in the manifest files to storage.
     */
    private function upload()
    {
        $disk = $this->disk;

        $this->info(sprintf("Start asset upload to %s...\n", $this->disk));

        array_walk($this->manifest, function ($asset) {
            $src = sprintf('%s%s/%s', public_path(), $this->build_source, parse_url($asset)['path']);
            $src = str_replace('//', '/', $src);
            
            $dest = sprintf('%s/%s', $this->build_dest, $this->cdnify->renameQueryString($asset));
            $dest = str_replace('//', '/', $dest);
                        
            // Does the file exist locally?
            if (!file_exists($src)) {
                $this->_comment(sprintf('Skipping. Local file doesn\'t exist. (%s)', $src));
                $this->counts['skipped']++;
                
                return $asset;
            }

            // Storing the file now...
            $storage = Storage::disk($this->disk);

            // Exists already on S3 check...
            if ($storage->exists($dest) && $this->force == false) {
                $this->_comment(sprintf('Skipping. Asset exists on %s. (%s)', $this->disk, $dest));
                $this->counts['skipped']++;
                
                return $asset;
            }

            // Store!
            $this->_comment(sprintf('Sending asset to %s. (%s)', $this->disk, $dest));

            if ($storage->put($dest, file_get_contents($src)) !== true) {
                $this->_error('Fail...');
                throw new \Exception('Sending asset failed, aborting!', 1);
            }
            
            $this->counts['uploaded']++;
            
            if ($this->detail) {
                $this->_info('Success...');
            }
        });
        
        $this->newline();
        $this->info(sprintf('Asset upload to %s completed.', $this->disk));
        $this->comment(sprintf('%d files uploaded', $this->counts['uploaded']));
        $this->comment(sprintf('%d files skipped', $this->counts['skipped']));
    }

    /**
     * Calls systems commands.
     *
     * @param string $cmd
     *
     * @return bool
     */
    private function system($cmd)
    {
        $this->info(sprintf('Start system command. (%s)', $cmd));

        if (!system($cmd)) {
            $this->error(sprintf('System command failed... (%s)', $cmd));
            throw new \Exception('System command failed.', 1);
        }

        $this->info(sprintf('End system command. (%s)', $cmd));

        return true;
    }

    /**
     * Validates json data.
     *
     * @param string $json
     *
     * @return string
     */
    private function isValidJson($json)
    {
        $json = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid json file.');
        }

        return $json;
    }

    /**
     * Helper method to make new lines, and comments look pretty!
     */
    private function newline()
    {
        $this->info('');
    }
    
    private function _info($string, $verbosity = null)
    {
        if ($this->detail) {
            return $this->info($string);
        }
    }
    
    private function _comment($string, $verbosity = null)
    {
        if ($this->detail) {
            return $this->comment($string);
        }
    }
    
    private function _error($string, $verbosity = null)
    {
        if ($this->detail) {
            return $this->error($string);
        }
    }
}
