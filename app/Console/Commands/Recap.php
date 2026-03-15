<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Recap extends Command
{
    protected $signature = 'recap {count?}';

    protected $description = 'see the previous commits without logging on to the git or typing git log and seeing a too much info';
    
    /**
    * run 'php artisan recap insert'
    */
    public function handle()
    {
        $count = $this->argument('count') ?? 5;
        $command = "git log -n $count --pretty=format:'%s' --no-merges --reverse";
        
        exec($command, $output);
        
        $this->line("");

        foreach ($output as $line) {
            $this->line(" - " . $line);
            $this->line("");
        }
    }
}
