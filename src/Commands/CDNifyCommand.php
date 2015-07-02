<?php

namespace Metrique\CDNify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CDNifyCommand extends Command {

    const CONSOLE_INFO = 0;
    const CONSOLE_ERROR = 1;
    const CONSOLE_COMMENT = 2;

    protected $config;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'metrique:cdnify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy laravel-elixir versioned assets to a file system.';

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
     * The build paths to use.
     * 
     * @var string
     */
    protected $build;

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
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->defaults();
        $this->options();
        $this->newline();

        $this->output(self::CONSOLE_COMMENT, 'php artisan metrique:cdnify');

        $this->output(self::CONSOLE_INFO, 'This will compile and upload assets from ' . $this->manifest . ' to your chosen data store (' . $this->disk .')...', true);


        if ($this->confirm('Do you wish to continue?'))
        {
            try {
                // 1. Compile, copy and version assets.
                $this->elixir();

                // 2. Load newly created manifest file and parse ready for asset upload!
                $this->manifest();

                // 3. Upload the files.
                $this->upload();


            } catch (\Exception $e)
            {
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
        $this->build_source = Config::get('build_source', '/build');
        $this->build_dest = Config::get('build_dest', '');
        $this->disk = Config::get('disk', 's3');
        $this->force = Config::get('force', false);
        $this->manifest = Config::get('manifest', '/build/rev-manifest.json');
    }

    /**
     * Parses the command line options.
     * 
     * @return void
     */
    private function options() {

        // Build
        $this->build_source = $this->option('build-source');
        $this->build_dest = $this->option('build-dest');

        // Disk
        $this->disk = $this->option('disk');

        if(!in_array($this->disk, $this->validDisks)) {
            $this->output(self::CONSOLE_ERROR, 'Disk not supported.');
            throw new \Exception('Disk not supported, aborting!', 1);
        }

        // Force
        $this->force = $this->option('force');

        // Manifest
        $this->manifest = $this->option('manifest');
    }

    /**
     * Runs Elixir or Gulp in production mode.
     * 
     * @return void
     */
    private function elixir()
    {
        $this->system('gulp --install --production');
    }

    /**
     * Reads the manifest file.
     * 
     * @return void
     */
    private function manifest()
    {
        $manifestFile = public_path() . $this->manifest;
        $this->manifest = null;

        if(file_exists($manifestFile))
        {
            $this->manifest = $this->isValidJson(file_get_contents($manifestFile));
        }           
    }

    /**
     * Transmits assets included in the manifest files to storage.
     * 
     * @return void
     */
    private function upload()
    {
        $disk = $this->disk;

        $this->newline();
        $this->output(self::CONSOLE_INFO, 'Start asset upload to ' . $this->disk .'.');

        array_walk($this->manifest, function($asset)
        {
            $src = public_path() . $this->build_source . '/' . $asset;
            $dest = $this->build_dest . '/' . $asset;

            // Does the file exist locally?
            if(!file_exists($src)) {

                $this->output(self::CONSOLE_COMMENT, 'Skipping. Local file doesn\'t exist. ('. $asset .')');

                return $asset;
            }

            // Storing the file now...
            $storage = Storage::disk($this->disk);

            // Exists already on S3 check...
            if($storage->exists($dest) && $this->force == false)
            {
                $this->output(self::CONSOLE_COMMENT, 'Skipping. Asset exists on ' . $this->disk . '. (' . $asset . ')');

                return $asset;
            }

            // Store!
            $this->output(self::CONSOLE_COMMENT, 'Sending asset to ' . $this->disk . '... (' . $dest . ')');

            if($storage->put($dest, file_get_contents($src)) !== true) {
                $this->output(self::CONSOLE_ERROR, 'Fail...');
                throw new \Exception('Sending asset failed, aborting!', 1);
            }

            $this->output(self::CONSOLE_INFO, 'Success...');
        });

        $this->output(self::CONSOLE_INFO, 'End asset upload to ' . $this->disk .'.');
    }

    /**
     * Calls systems commands.
     * 
     * @param string $cmd 
     * @return bool
     */
    private function system($cmd)
    {
        $this->newline();
        $this->output(self::CONSOLE_INFO, 'Start system command. (' . $cmd . ')');

        if(!system($cmd))
        {
            $this->output(self::CONSOLE_ERROR, 'System command failed...');
            throw new \Exception('System command failed.', 1);
        }

        $this->output(self::CONSOLE_INFO, 'End system command. (' . $cmd . ')');

        return true;
    }

    /**
     * Validates json data.
     * 
     * @param string $json 
     * @return string
     */
    private function isValidJson($json)
    {
        $json = json_decode($json, true);

        if(json_last_error() !== JSON_ERROR_NONE)
        {
            throw new \Exception('Invalid json file.');
        }

        return $json;
    }

    /**
     * Outputs information to the console.
     * 
     * @param int $mode 
     * @param string $message 
     * @return void
     */
    private function output($mode, $message, $newline = false) {

        $newline = $newline ? PHP_EOL : '';

        switch ($mode) {
            case self::CONSOLE_COMMENT:
                $this->comment('[-msg-] ' . $message . $newline);
            break;

            case self::CONSOLE_ERROR:
                $this->error('[-err-] ' . $message . $newline);
            break;
            
            default:
                $this->info('[-nfo-] ' . $message . $newline);
            break;
        }
    }

    /**
     * Helper method to make new lines, and comments look pretty!
     * 
     * @return void
     */
    private function newline() {
        $this->info('');    
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            // ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['build-source', 'bs', InputOption::VALUE_OPTIONAL, 'Set build path.', $this->build],
            ['build-dest', 'bd', InputOption::VALUE_OPTIONAL, 'Set build path.', $this->build],
            ['disk', 'd', InputOption::VALUE_OPTIONAL, 'Set disk/upload method.', $this->disk],
            ['force', 'f', InputOption::VALUE_NONE, 'Toggle force upload of files.', null],
            ['manifest', 'm', InputOption::VALUE_OPTIONAL, 'Set manifest location.', $this->manifest],
        ];
    }
}