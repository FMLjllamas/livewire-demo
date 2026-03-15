<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class OOMXLPlate extends Command
{

    protected $signature = 'boilerplate:make:oomxl {controllername} {documentname} {oomxlfile}';

    // prob needs a controller to recieve the document and the service itself.
    protected $description = 'creates the base template for docx creation.';

    /*
    docx controller + service
    */

    public function handle()
    {
        $controllername = $this->argument('controllername');
        $docname = $this->argument('documentname');
        $oomxlfile = $this->argument('oomxlfile');
        // ensure no file overwrite
        $path_name = 'app\Http\Controllers\\' . $controllername .  '.php';
        $path = base_path( $path_name );
        
        if (File::exists($path)) {
            $this->error("Test file {$path} already exists!");
            return;
        }

        // main heredoc
        $boilerplate = 
        <<<PHP
        <?php
        namespace App\Http\Controllers;

        use Illuminate\Http\Request;
        use App\Models\UserProfile;
        use Illuminate\Support\Facades\Log;
        use PhpOffice\PhpWord\IOFactory;

        use App\Services\OOMXL\\$oomxlfile;

        class $controllername extends Controller
        {

            public function index(Request \$request)
            {
                try {
                    //get docx
                    \$title = $docname;
                    \$doc = $oomxlfile::getOOMXL(\$title);

                    \$testing = true;

                if (\$testing) {

                    // HTML viewing for testing

                    \$writer = IOFactory::createWriter(\$doc, 'HTML');

                    ob_start();
                    \$writer->save('php://output');
                    \$htmlContent = ob_get_clean();

                    return response(\$htmlContent, 200)->header('Content-Type', 'text/html');

                } else {
                    \$writer = IOFactory::createWriter(\$doc, 'Word2007');

                    return response()->stream(function () use (\$writer) 
                        {
                            \$writer->save('php://output');
                        }, 
                        200, 
                        [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'Content-Disposition' => 'inline; filename="report.docx"',
                        ]
                    );

                }
                    
                } catch (\Exception \$e) {
                    Log::error(\$e->getMessage());

                    return response()->json([
                        'errors' => [true],
                        'message' => \$e->getMessage(),
                    ], 400);
                }
            }
        }
        PHP;

        File::put($path, $boilerplate);
        
        $this->info("docx service template controller created");
    }
}
