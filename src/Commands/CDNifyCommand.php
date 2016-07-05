<?php

namespace Metrique\CDNify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CDNifyCommand extends Command
{
    const CONSOLE_INFO = 0;
    const CONSOLE_ERROR = 1;
    const CONSOLE_COMMENT = 2;

    protected $config;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'metrique:cdnify
                            {--source=/build : Set build source path.}
                            {--dest=/build : Set build dest path.}
                            {--disk=s3 : Set disk/upload method.}
                            {--force : Toggle force upload of files.}
                            {--manifest=/build/rev-manifest.json : Set manifest location.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy laravel-elixir versioned assets.';

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
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->defaults();

        $this->options();

        $this->newline();

        $this->output(self::CONSOLE_COMMENT, 'php artisan metrique:cdnify');

        $this->output(self::CONSOLE_INFO, 'This will compile and upload assets from '.$this->manifest.' to your chosen data store ('.$this->disk.')...', true);

        if ($this->confirm('Do you wish to continue?')) {
            try {
                // 1. Compile, copy and version assets.
                $this->elixir();

                // 2. Load newly created manifest file and parse ready for asset upload!
                $this->manifest();

                // 3. Upload the files.
                $this->upload();
            } catch (\Exception $e) {
                $this->output(self::CONSOLE_ERROR, 'Encountered an unknown error processing this request, please try again.');
            }
        }

        $this->newline();
        $this->output(self::CONSOLE_INFO, 'Finished...', true);
    }

    /**
     * Loads defaults from the config file.
     */
    private function defaults()
    {
        $this->build_source = config('cdnify.command.build_source', '/build');
        $this->build_dest = config('cdnify.command.build_dest', '');
        $this->disk = config('cdnify.command.disk', 's3');
        $this->force = config('cdnify.command.force', false);
        $this->manifest = config('cdnify.command.manifest', '/build/rev-manifest.json');
    }

    /**
     * Parses the command line options.
     */
    private function options()
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
            $this->output(self::CONSOLE_ERROR, 'Disk not supported.');
            throw new \Exception('Disk not supported, aborting!', 1);
        }

        // Force
        if (is_bool($this->option('force'))) {
            $this->force = $this->option('force');
        }

        // Manifest
        if (is_string($this->option('manifest'))) {
            $this->manifest = $this->option('manifest');
        }
    }

    /**
     * Runs Elixir or Gulp in production mode.
     */
    private function elixir()
    {
        $this->system('gulp --production');
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

        $this->newline();
        $this->output(self::CONSOLE_INFO, 'Start asset upload to '.$this->disk.'.');

        array_walk($this->manifest, function ($asset) {
            $src = public_path().$this->build_source.'/'.$asset;
            $dest = $this->build_dest.'/'.$asset;

            // Does the file exist locally?
            if (!file_exists($src)) {
                $this->output(self::CONSOLE_COMMENT, 'Skipping. Local file doesn\'t exist. ('.$asset.')');

                return $asset;
            }

            // Storing the file now...
            $storage = Storage::disk($this->disk);

            // Exists already on S3 check...
            if ($storage->exists($dest) && $this->force == false) {
                $this->output(self::CONSOLE_COMMENT, 'Skipping. Asset exists on '.$this->disk.'. ('.$asset.')');

                return $asset;
            }

            // Store!
            $this->output(self::CONSOLE_COMMENT, 'Sending asset to '.$this->disk.'... ('.$dest.')');

            if ($storage->put($dest, file_get_contents($src)) !== true) {
                $this->output(self::CONSOLE_ERROR, 'Fail...');
                throw new \Exception('Sending asset failed, aborting!', 1);
            }

            $this->output(self::CONSOLE_INFO, 'Success...');
        });

        $this->output(self::CONSOLE_INFO, 'End asset upload to '.$this->disk.'.');
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
        $this->newline();
        $this->output(self::CONSOLE_INFO, 'Start system command. ('.$cmd.')');

        if (!system($cmd)) {
            $this->output(self::CONSOLE_ERROR, 'System command failed...');
            throw new \Exception('System command failed.', 1);
        }

        $this->output(self::CONSOLE_INFO, 'End system command. ('.$cmd.')');

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
     * Outputs information to the console.
     *
     * @param int    $mode
     * @param string $message
     */
    private function output($mode, $message, $newline = false)
    {
        $newline = $newline ? PHP_EOL : '';

        switch ($mode) {
            case self::CONSOLE_COMMENT:
                $this->comment('[-msg-] '.$message.$newline);
                break;

            case self::CONSOLE_ERROR:
                $this->error('[-err-] '.$message.$newline);
                break;

            default:
                $this->info('[-nfo-] '.$message.$newline);
                break;
        }
    }

    /**
     * Helper method to make new lines, and comments look pretty!
     */
    private function newline()
    {
        $this->info('');
    }
}
