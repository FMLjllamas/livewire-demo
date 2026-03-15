<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PestPlate extends Command
{
    protected $signature = 'boilerplate:make:pest {name} {model}';

    protected $description = 'generates pest style boilerplate';

    /**
    * run 'php artisan pestplate:make nameyouwant'
    */
    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->argument('model');

        $path = base_path("tests/Feature/{$name}.php");
        
        if (File::exists($path)) {
            $this->error("Test file {$path} already exists!");
            return;
        }

        // generated file
        $boilerplate = 
        <<<PHP
            <?php

            use App\Models\User;
            use function Pest\Laravel\actingAs;
            use Illuminate\Support\Facades\Storage;
            use App\Models\\$model;

            use Illuminate\Foundation\Testing\DatabaseMigrations;
            uses(DatabaseMigrations::class);

            function storeModel(User \$user, array \$data = [], int \$expectedstatus = 200): ?$model {

                Storage::fake('local');

                actingAs(\$user, 'api')
                    ->post('routepath', \$data, ['Accept' => 'application/json'])
                    ->assertStatus(\$expectedstatus);
                    
                return $model::latest()->first();

            }

            function updateModel(User \$user, int \$id, array \$data = [], int \$expectedstatus = 200): void {
                
                actingAs(\$user, 'api')
                    ->put("routepath/{\$id}", \$data, ['Accept' => 'application/json'])
                    ->assertStatus(\$expectedstatus);

            }

            function destroyModel(User \$user, int \$id, int \$expectedstatus = 200): void {

                actingAs(\$user, 'api')
                    ->delete("routepath/{\$id}")
                    ->assertStatus(\$expectedstatus);

            }

            // basics
            test('1) store-update-delete', function () {

                \$user = User::factory()->create();

                \$model = storeModel(\$user, [
                    'field' => 'value',
                    'field2' => 'value2'
                ]);

                
                updateModel(\$user, \$model->id, [
                    'field' => 'updated value',
                    'field2' => 'updated value2'
                ]);

                destroyModel(\$user, \$model->id);
            
            });

            test('2) insufficient store values', function () {

                \$user = User::factory()->create();

                \$model = storeModel(\$user, [

                    'field' => 'updated value'
                    //missing value

                ], 422);   

                expect(\$model)->toBeNull(); // if validation doesnt pass itll be null
            });


            test('3) wrong formatted update', function () {

                \$user = User::factory()->create();

                \$model = storeReport(\$user, [
                    'field' => 'updated value'
                ]);
                
                updateModel(\$user, \$model->id, [
                    'field' => 1 // wrong value format
                ], 422);

                destroyModel(\$user, \$model->id);

            });
        PHP;

        File::put($path, $boilerplate);

        $this->info("test created");
    }
}
