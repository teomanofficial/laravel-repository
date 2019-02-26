<?php

namespace Hsntngr\Repository\Commands;

use Illuminate\Console\Command;

class RepositoryList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all created repositories';

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
     * @return mixed
     */
    public function handle()
    {
        foreach (config("repository.map") as $key => $repository) {
            $n = 15 - strlen($key);
            $this->line($key . $this->space($n) . "->  " . $repository);
        }
    }

    public function space($n)
    {
        return str_repeat(" ", $n <= 0 ? 0 : $n);
    }
}
