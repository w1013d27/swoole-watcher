<?php

namespace HuangYi\Watcher\Commands;

use HuangYi\Watcher\Contracts\Command;
use HuangYi\Watcher\Exceptions\CommandNotFoundException;
use Symfony\Component\Process\ExecutableFinder;

class Fswatch implements Command
{
    const NO_OP              = 0;
    const PLATFORM_SPECIFIC  = 1;
    const CREATED            = 2;
    const UPDATED            = 4;
    const REMOVED            = 8;
    const RENAMED            = 16;
    const OWNER_MODIFIED     = 32;
    const ATTRIBUTE_MODIFIED = 64;
    const MOVED_FROM         = 128;
    const MOVED_TO           = 256;
    const IS_FILE            = 512;
    const IS_DIR             = 1024;
    const IS_SYMLINK         = 2048;
    const LINK               = 4096;
    const OVERFLOW           = 8192;

    /**
     * The user-specified binary path.
     *
     * @var string
     */
    protected $binary;

    /**
     * The path to be watched.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * The watched events.
     *
     * @var int
     */
    protected $event;

    /**
     * The latency time in seconds.
     *
     * @var float
     */
    protected $latency = 0.0001;

    /**
     * A file to set the path filters.
     *
     * @var string
     */
    protected $filterFrom = null;

    /**
     * Indicates if track directories recursively.
     *
     * @var bool
     */
    protected $recursive = true;

    /**
     * Indicates if use case insensitive regular expressions.
     *
     * @var bool
     */
    protected $insensitive = true;

    /**
     * The fixed options.
     *
     * @var array
     */
    protected $fixedOptions = [
        '--numeric'     => true,
        '--extended'    => true,
        '--event-flags' => true,
    ];

    /**
     * The user-defined options.
     *
     * @var array
     */
    protected $userOptions = [];

    /**
     * Fswatch constructor.
     *
     * @param  string  $path
     * @param  string  $binary
     * @return void
     */
    public function __construct($path, $binary = null)
    {
        $this->addPath($path);

        $this->binary = $binary;
    }

    /**
     * Get the executable command.
     *
     * @return array
     */
    public function getCommand(): array
    {
        return [
            $this->getExecutableBinary(),
            array_merge(
                $this->concatOptions(),
                $this->getPaths()
            ),
        ];
    }

    /**
     * Parse events from the outputs.
     *
     * @param  string  $outputs
     * @return array
     */
    public function parseEvents(string $outputs): array
    {
        $events = [];

        foreach (explode("\n", trim($outputs)) as $line) {
            $pieces = explode(' ', $line);

            $events[] = [
                'path' => $pieces[0],
                'events' => $pieces[1],
            ];
        }

        return $events;
    }

    /**
     * Get the executable binary.
     *
     * @return string
     */
    protected function getExecutableBinary()
    {
        if ($this->binary) {
            $binary = $this->binary;
        } else {
            $binary = (new ExecutableFinder)->find('fswatch');
        }

        if (! $binary) {
            throw new CommandNotFoundException("Command 'fswatch' not found.");
        }

        if (! @is_executable($binary)) {
            throw new CommandNotFoundException("Command '$binary' is not executable.");
        }

        return $binary;
    }

    /**
     * Concat the options.
     *
     * @return array
     */
    protected function concatOptions()
    {
        $options = [];

        foreach ($this->getOptions() as $key => $value) {
            if ($value === true) {
                $options[] = $key;
            } elseif ($value) {
                $options[] = $key.'='.$value;
            }
        }

        return $options;
    }

    /**
     * Get the options.
     *
     * @return array
     */
    public function getOptions()
    {
        $defaultOptions = [
            '--event'       => $this->event,
            '--latency'     => $this->latency,
            '--filter-from'   => $this->filterFrom,
            '--recursive'   => $this->recursive,
            '--insensitive' => $this->insensitive,
        ];

        return array_merge($defaultOptions, $this->userOptions, $this->fixedOptions);
    }

    /**
     * Get the paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return array_keys($this->paths);
    }

    /**
     * Add a path.
     *
     * @param  string  $path
     * @return $this
     */
    public function addPath($path)
    {
        if (is_array($path)) {
            return $this->addPaths($path);
        }

        $path = trim($path);

        $this->paths[$path] = true;

        return $this;
    }

    /**
     * Add paths.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }

    /**
     * Get the event.
     *
     * @return int
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set the event.
     *
     * @param  int  $event
     * @return $this
     */
    public function setEvent(int $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get the latency.
     *
     * @return float
     */
    public function getLatency()
    {
        return $this->latency;
    }

    /**
     * Set the latency.
     *
     * @param  float  $latency
     * @return $this
     */
    public function setLatency(float $latency)
    {
        $this->latency = $latency;

        return $this;
    }

    /**
     * Get the filter file.
     *
     * @return string
     */
    public function getFilterFrom()
    {
        return $this->filterFrom;
    }

    /**
     * Set the filter file.
     *
     * @param  string  $filterFrom
     * @return $this
     */
    public function setFilterFrom(string $filterFrom)
    {
        $this->filterFrom = $filterFrom;

        return $this;
    }

    /**
     * Get the recursive.
     *
     * @return bool
     */
    public function getRecursive()
    {
        return $this->recursive;
    }

    /**
     * Set the recursive.
     *
     * @param  bool  $recursive
     * @return $this
     */
    public function setRecursive(bool $recursive)
    {
        $this->recursive = $recursive;

        return $this;
    }

    /**
     * Get the insensitive.
     *
     * @return bool
     */
    public function getInsensitive()
    {
        return $this->insensitive;
    }

    /**
     * Get the insensitive.
     *
     * @param  bool  $insensitive
     * @return $this
     */
    public function setInsensitive(bool $insensitive)
    {
        $this->insensitive = $insensitive;

        return $this;
    }

    /**
     * Get the user-defined options.
     *
     * @return array
     */
    public function getUserOptions()
    {
        return $this->userOptions;
    }

    /**
     * Set the user-defined options.
     *
     * @param  array  $options
     * @return $this
     */
    public function setUserOptions(array $options)
    {
        $this->userOptions = $options;

        return $this;
    }
}
