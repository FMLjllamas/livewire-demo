<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FileHandlerPlate extends Command
{
    protected $signature = 'boilerplate:make:filehandler {name?} {model?} {modelfile?} {--hash}';

    protected $description = 'generates filehandler boilerplate using the model you\'d like';

    public function handle()
    {
        $name = $this->argument('name') ?? $this->error("specify name attribute please");; // service name

        $model = $this->argument('model') ?? $this->error("specify model attribute please");; // main data model

        $modelfile = $this->argument('modelfile') ?? $this->error("specify model file attribute please");; // data model of file

        if ($name == null || $model == null) {
            $this->error("specify name or model name please");
            return;
        }

        $path_name = 'app\Services\\' . $name .  '.php';
        $path = base_path( $path_name );
        
        if (File::exists($path)) {
            $this->error("{$path} already exists!");
            return;
        }

        if ( $this->option('hash') ) {
            $filename = 'md5($ogfilename . now())' ;
        } else {
            $filename = '$ogfilename';
        }

        $boilerplate = 
        <<<PHP
        <?php

        namespace App\Services;

        use App\Models\\$model;
        use App\Models\\$modelfile;
        use Illuminate\Support\Facades\Log;
        use Illuminate\Http\Request;
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Storage;

        class $name
        {
            public const FILE_UPLOADS_MAX_ALLOWED = 1;
            
            /** 
             * this uses Request to attach the files to the model.
            */
            public static function handle(\$id, Request \$request, \$path) 
            {                    

                \$item = $model::find(\$id);

                if (!\$request->hasFile('files')) return true;

                DB::beginTransaction(); 

                try {

                    \$files = \$request->file('files') ?? [];

                    foreach( \$files as \$file ) {

                        \$file_duplicate = \$item->files->firstWhere('original_filename', \$file->getClientOriginalName()); 

                        if (\$file_duplicate) {
                            Storage::delete( \$file_duplicate->file_path ); // delete file from disk
                            \$file_duplicate->delete(); // delete db record
                        }

                        Storage::disk('local')->makeDirectory(\$path); // auto checks if it exists

                        // file info
                        \$model_id = (new $model)->getForeignKey();
                        \$item_id = \$item->id;
                        \$mime = \$file->extension();
                        \$ogfilename = \$file->getClientOriginalName();
                        \$filesize = \$file->getSize();

                        \$filename = $filename;

                        Storage::disk('local')->putFileAs(\$path, \$file, \$ogfilename . '.' . \$mime);

                        $modelfile::create([
                            \$model_id => \$item_id,
                            'file_name' => \$ogfilename,
                            'original_filename' => \$ogfilename,
                            'mime_type' => \$mime,
                            'file_size' => \$filesize,

                            'file_path' => self::fixslashes(\$path . \$filename . \$mime)
                        
                        ]);
                
                    }

                } catch ( \Throwable \$e) {
                    DB::rollBack();
                    Log::error("FileHandler failed: " . \$e->getMessage(), ['exception' => \$e]);            
                    return false;
                }
                DB::commit();
                return true;
            }

            /** 
             * this deletes all files related to the model
            */
            public static function destroy(\$id, $model \$item)  
            {
                \$item = $model::find(\$id);

                if (!\$item || !\$item->files()->exists()) return; // exit early if no files

                \$folder = dirname(\$item->files()->first()->file_path);

                foreach (\$item->files as \$file) {
                    Storage::delete( \$file->file_path );
                    \$file->delete(); // delete record from db
                }

                \$userfolder = dirname(\$folder);

                if (Storage::disk('local')->exists(\$folder) &&
                    empty(Storage::disk('local')->files(\$folder)) &&
                    empty(Storage::disk('local')->directories(\$folder))) {

                    Storage::disk('local')->deleteDirectory(\$folder);
                }
            }

            /**
             * truncates the amount of incoming files from the request to whatever space is left.
             * this changes request itself. it does not output any files.
             */
            public static function trim($model \$item, Request \$request): bool
            {
                if (!\$request->hasFile('files')) return false; 

                \$incomingFiles = \$request->file('files') ?? [];
                \$maxAllowed = max(0, self::FILE_UPLOADS_MAX_ALLOWED - \$item->files->count() );
                \$filesToValidate = array_slice(\$incomingFiles, 0, \$maxAllowed);

                \$request->files->set('files', \$filesToValidate);

                return true;
            }

            /**
             * replaces all backslashes with forward slashes in a string just in case.
             */
            function fixslashes(string \$string): string
            {
                return str_replace(['\\\\', '/'], DIRECTORY_SEPARATOR, \$string);
            }
        }
        PHP;
        

        File::put($path, $boilerplate);

        $this->info("file handling service created");
    }


}
