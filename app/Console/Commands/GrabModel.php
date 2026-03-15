<?php

namespace App\Console\Commands;

use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GrabModel extends Command
{
    protected $signature = 'model:show {model} {id?} {--fresh} {--relations}'; // command

    protected $description = 'refresh and give me the model with id';

    public function handle()
    {
        // either get the first model or the id num u want
        $id = $this->argument('id') ?? 1;
        $modelname = $this->argument('model');
        $modelclass = "App\\Models\\" . ucfirst($modelname);

        if (!class_exists($modelclass)) {
            $this->error("Model {$modelclass} does not exist!");
            return;
        }

        // if --fresh then redo the DB
        if ($this->option('fresh')) {
            $this->info('attempting a fresh db...');
            Artisan::call('migrate:refresh', ['--seed' => true]);
            $this->info('successfully created fresh db');
        } else {
            $this->info('we\'ll be using existing db');
        }

        if ($this->option('relations')) {
            // relationship grabbing
            $instance = new $modelclass;
            $relations = self::getrelationships($instance);
            $this->info("Detected relationships: " . implode(', ', $relations));
            // model to grab
            $model = $modelclass::with($relations)->findOrFail($id);
        }

        $instance = new $modelclass;
        $model = $instance->findOrFail($id);

        // print
        $this->info('data : ');       
        $this->line($model->toJson(JSON_PRETTY_PRINT));
        $this->info('TADAAAA!!!');

    }

    protected static function getrelationships($model)
    {
        $relationships = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getNumberOfParameters() > 0 || $method->class === 'Illuminate\Database\Eloquent\Model') {
                continue;
            }

            try {
                $return = $method->invoke($model);
                if ($return instanceof Relation) {
                    $relationships[] = $method->getName();
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $relationships;
    }
}
