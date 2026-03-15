<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ControllerPlate extends Command
{
    // boilerplate:make:controller {name} {model} {type} {--files}
    protected $signature = 'boilerplate:make:controller 
    {name : class name of the controller} 
    {model : the eloquent model youre handling} 
    {type : select: store, crud, or stats to define the controller type} 
    {--files : generates an associated filehandler class and filehandler method}';

    protected $description = 'creates a pre-configured controller with validation, logging, and optional file handling.';

    /**
     * 1. validate file existence to prevent accidental overwrites.
     * 2. if --files is present, trigger the FileHandler sub-command.
     * 3. Select the template block based on the {type} argument.
     * 4. put metadata (table names, model names) into the main heredoc.
     * 5. write the rest of the controller depending on the type.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->argument('model');
        $type = $this->argument('type') ?? 'crud';

        // ensure no file overwrite
        $path_name = 'app\Http\Controllers\\' . $name .  '.php';
        $path = base_path( $path_name );
        
        if (File::exists($path)) {
            $this->error("Test file {$path} already exists!");
            return;
        }

        // files option
        if ( $this->option('files') ) {

            $filehandler = $model . "FileHandler";
            $filemodel = $model . "File";
            $imports = <<<PHP
            use App\Services\\$filehandler;
            use App\Models\\$filemodel;
            PHP;

            $this->call('boilerplate:make:filehandler', [
                'name' => $filehandler,
                'model' => $model,
                'modelfile' => $filemodel,
            ]);

            $filemethodholder = <<<PHP
public function handleFileUpload(\$id, Request \$request) 
{
    \$item = $model::findOrFail(\$id);

    if ( !$filehandler::trim(\$item, \$request) ) return;

    \$request->validate([
        'files' => 'nullable|array|max:1',
        'files.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx',
    ]);
    
    \$files = \$request->file('files') ?? [];

    \$attachmentspath = 'path/' ;
    abort_unless($filehandler::handle(\$item->id, \$files, \$attachmentspath), 422, 'Failed to upload files');

    Log::info('Files uploaded successfully', [ 'payload' => \$id ]);

    return response()->json([
        'message' => 'Files uploaded successfully'
    ]);
}
PHP;
        } else {
            $imports = '';
            $filemethodholder = '';
        }

        $code = '';
        $gate = strtoLower($model);

        // controller type
        if ($type == 'store') {
        $code = <<<PHP
                // store
                public function store(Request \$request)
                {
                    abort_if(Gate::denied('$gate.create'), Response::HTTP_FORBIDDEN, 'Forbidden');

                    \$request->validate([
                        'field' => 'type'
                    ]);

                    \$item = new $model([
                        'user' => Auth::user()->email,
                        'field' => 'type'
                    ]);

                    \$item->save();

                    if (\$item->save()) {
                        
                        Log::info(
                            'New item created',
                            ['payload' => \$item->id]
                        );

                        return response()->json([
                            'message' => 'New item created'
                        ]);
                    }

                }
        PHP;
        } elseif ($type == 'crud') {
        $code = <<<PHP
                // index
                public function index(Request \$request)
                {
                    abort_if(Gate::denied('$gate.read'), Response::HTTP_FORBIDDEN, 'Forbidden'); // change

                    // limit page size
                    \$count = \$request->perpage ?
                        (\$request->perpage < config('app.page_size_limit') ? \$request->perpage : config('app.page_size_limit'))
                        : config('app.page_size');


                    // sortable by this 
                    \$pipeline = [
                        \App\Filters\SearchFor::class,
                        \App\Filters\OrderBy::class
                    ];

                
                    try {

                        return Pipeline::send(
                            $model::query()->where('user', Auth::user()->email) // change
                        )
                            ->through(\$pipeline)
                            ->thenReturn()
                            ->paginate(\$count);

                    } catch (\Exception \$e) {

                        Log::error(\$e->getMessage());

                        return response()->json([
                            'errors' => [true],
                            'message' => 'Could not obtain information.'
                        ]);
                    }
                }

                // show
                public function show(\$id)
                {
                    abort_if(Gate::denied('$gate.read'), Response::HTTP_FORBIDDEN, 'Forbidden');

                    return $model::where('user', Auth::user()->email)->findOrFail(\$id);
                }

                // store
                public function store(Request \$request)
                {
                    abort_if(Gate::denied('$gate.create'), Response::HTTP_FORBIDDEN, 'Forbidden');

                    \$request->validate([
                        'field' => 'type'
                    ]);

                    \$item = new $model([
                        'user' => Auth::user()->email,
                        'field' => 'type'
                    ]);

                    \$item->save();

                    if (\$item->save()) {
                        
                        Log::info(
                            'New item created',
                            ['payload' => \$item->id]
                        );

                        return response()->json([
                            'message' => 'New item created'
                        ]);
                    }

                }

                // update
                public function update(\$id, Request \$request) 
                {
                    abort_if(Gate::denied('$gate.update'), Response::HTTP_FORBIDDEN, 'Forbidden');

                    \$item = $model::where('user', Auth::user()->email)->findOrFail(\$id);

                    \$request->validate([
                        'field' => 'type'
                    ]);

                    \$old_item = clone \$item;
                    
                    \$item->fill([
                        'librarian' => Auth::user()->email,
                        'field' => 'updated value'
                    ]);

                    abort_unless(\$item->isDirty(), 400, 'No changes');
                    abort_unless(\$item->save(), 500, 'Save failed');

                    Log::info('item updated successfully', [

                        'payload' => \$item->id,
                        'updated_json' => json_encode(Diff::obj_diff(\$old_item->toArray(), \$item->toArray()))

                    ]);

                    return response()->json([
                        'message' => 'item updated successfully'
                    ]);

                }

                // destroy
                public function destroy(\$id) 
                {

                    abort_if(Gate::denied('$gate.delete'), Response::HTTP_FORBIDDEN, 'Forbidden');

                    \$item = $model::where('user', Auth::user()->email)->findOrFail(\$id);

                    abort_unless(\$item->delete(), 422, 'item was not deleted');

                    return response()->json([
                        'message' => 'item deleted successfully'
                    ]);

                }

                // export
                public function export()
                {
                    
                    abort_if(Gate::denied('$gate.read'), Response::HTTP_FORBIDDEN, 'Forbidden');

                    \$pipeline = [
                        \App\Filters\SearchFor::class,
                        \App\Filters\OrderBy::class
                    ];

                    \$rows = [];

                    try {

                        Pipeline::send( $model::query()->where('user', Auth::user()->email) )
                            ->through(\$pipeline)
                            ->thenReturn()
                            ->chunk(2500, function (\$items) use (&\$rows) {

                                foreach (\$items->toArray() as \$item) {
                                    \$rows[] = \$item;
                                }
                            });
                    } catch (\Illuminate\Database\QueryException \$e) {

                        Log::error(\$e->getMessage());

                        return response()->json([
                            'error' => [true],
                            'message' => 'Invalid query'

                        ]);
                    }

                    SimpleExcelWriter::streamDownload('schemaname' . Carbon::now() . ".xlsx") // format into excel
                        ->addRows(\$rows)
                        ->toBrowser();
                }
        PHP;
        } elseif ($type == 'stats') {
        $table = (new $model)->getTable();
        $code = <<<PHP
                public function index(Request \$request) {

                    abort_if(Gate::denied('$gate.read'), Response::HTTP_FORBIDDEN, 'Forbidden');
                    
                    // validate
                    \$request->validate([
                        'start' => 'required|date',
                        'end' => 'required|date|after_or_equal:start', // edge case
                        'report' => 'required|string|max:24'
                    ]);
                    
                    // variabels
                    \$start = \$request->start;
                    \$end = \$request->end;
                    \$stats = [];
                    
                    switch (\$request->report) {
                        case 'email':
            
                            \$stats =                                                                // the comments below are for my sake
                            \$model::select( 
                            'users.email AS requester',                                       // aliasing the emails as the asked variable
                            DB::raw('COUNT(*) AS number_of_requests')                           // aliasing count as the asked variable
                            )
                            ->join('users', '$table.email', '=', 'users.email') // link users and emails
                            ->whereBetween('$table.created_at', [\$start, \$end])                         // start to end constraint
                            ->groupBy('users.email')                                                // group by emails
                            ->orderBy('number_of_requests', 'desc')                             // sort by count, descending order
                            ->get();                                                                // return the list
                            
                            break;


                        case 'hour':

                            \$stats = 
                            \$model::select(
                            DB::raw("strftime('%H', $table.created_at) AS hour"), // NOTE, mysql is LPAD() which i had at first then remembered we're using sqllite
                            DB::raw('COUNT(*) AS number_of_requests')     // sqllite is strftime(format, value)
                            )
                            ->whereBetween('$table.created_at', [\$start, \$end])
                            ->groupBy( DB::raw("strftime('%H', $table.created_at)") )
                            ->orderBy( DB::raw("strftime('%H', $table.created_at)") )
                            ->get();

                            break;


                        case 'day':

                            \$stats = 
                            \$model::select(
                            DB::raw("strftime('%w', $table.created_at) AS day"),
                            DB::raw('COUNT(*) AS number_of_requests')
                            )
                            ->whereBetween('$table.created_at', [\$start, \$end])
                            ->groupBy( DB::raw("strftime('%w', $table.created_at)") )
                            ->orderBy( DB::raw("strftime('%w', $table.created_at)") )
                            ->get();

                            break;


                        case 'month':

                            \$stats = 
                            \$model::select(
                            DB::raw("strftime('%m', $table.created_at) AS month"),
                            DB::raw('COUNT(*) AS number_of_requests')
                            )
                            ->whereBetween('$table.created_at', [\$start, \$end])
                            ->groupBy( DB::raw("strftime('%m', $table.created_at)") )
                            ->orderBy( DB::raw("strftime('%m', $table.created_at)") )
                            ->get();

                            break;


                    default:
                        return response()->json([
                        'error' => 'Invalid report request'
                        ], 400);
                    
                    }

                    if (\$stats->isEmpty()) {
                        return response()->json([
                        'message' => 'No statistics are available during this period'
                        ]);
                    }
                    
                    Log::info('Successful statistics retreival');
                    return response()->json(\$stats); 
                }
        PHP;
        }

        // main heredoc
        $boilerplate = 
        <<<PHP
        <?php

        namespace App\Http\Controllers;

        use Exception;
        use Carbon\Carbon;
        use App\Services\Gate;
        use App\Services\Diff;
        use Illuminate\Http\Request;
        use Illuminate\Http\Response;
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Log;
        use Illuminate\Support\Facades\Auth;
        use Illuminate\Support\Facades\Pipeline;
        use Spatie\SimpleExcel\SimpleExcelWriter;
        
        use App\Models\\$model;
        $imports
        
        class $name extends Controller
        {
            
        $code
        
        $filemethodholder

        }
        PHP;

        File::put($path, $boilerplate);
        
        $this->info($type . " type controller created");
    }
}
