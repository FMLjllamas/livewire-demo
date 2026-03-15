<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class OOMXLTemPlate extends Command
{

    protected $signature = 'boilerplate:make:oomxltemplate {servicename} ';

    // prob needs a controller to recieve the document and the service itself.
    protected $description = 'creates the base template for docx creation.';

    /*
    left off here. need to make the service then test

    9:00 AM - 2:00 PM controller plate now creates boilerplate for statistics controllers, public controllers where its only store, full crud, and controllers needing file handling, 
    when file handling is needed the filehandler maker command is ran so the file handler is created too. 
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
        namespace App\Services\OOMXL;

        use App\Models\User;
        use PhpOffice\PhpWord\PhpWord;
        use PhpOffice\PhpWord\SimpleType\Jc;
        use PhpOffice\PhpWord\SimpleType\VerticalJc;

        class DivisionalDirectory
        {
            static function getOOMXL(\$title, \$records)
            {
                \$doc = new PhpWord();

                // document properties
                \$doc->getCompatibility()->setOoxmlVersion(15);
                
                \$properties = \$doc->getDocInfo();

                \$properties->setCreator('University Libraries Dashboard');
                \$properties->setTitle(\$title);
                \$properties->setSubject('');
                \$properties->setDescription('');

                // i dont like the verbose alignment.
                \$left = ['alignment' => Jc::START];
                \$right = ['alignment' => Jc::END];
                \$center = ['alignment' => Jc::CENTER];

                // styling
                \$style = 'FMLstyle'; // NOT BOLDED
                \$doc->addFontStyle(
                    \$style,
                    ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false]
                );

                \$styleB = 'FMLstyleB'; // BOLDED
                \$doc->addFontStyle(
                    \$styleB,
                    ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => true]
                );


                \$styleS = 'FMLstyleS'; // S => 'small' 9 pt font
                \$doc->addFontStyle(
                    \$styleS,
                    ['name' => 'Arial', 'size' => 9, 'color' => '000000', 'bold' => false]
                );
                
                // h1
                \$doc->addTitleStyle(
                    1, 
                    ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => true, 'italic' => true],
                    \$center
                );

                // h2
                \$doc->addTitleStyle(
                    2, 
                    [
                        'name' => 'Arial',
                        'size' => 12,
                        'color' => '000000', 
                        'bold' => true, 
                        'underline' => 'single'
                    ],
                    \$left
                );

                \$records = self::MapPersonnelRecordsToDepartments(\$records);

                // section 1        
                \$section = \$doc->addSection([
                    'marginTop'    => 360,   // 0.5 inch
                    'marginBottom' => 360,
                    'marginLeft'   => 360,
                    'marginRight'  => 360,
                ]);
                
                self::addHeaderFooter(\$section, \$title);

                return \$doc;
            }

            private static function addHeaderFooter(\$section, \$title) {        
                \$header = \$section->addHeader();
            
                \$table = \$header->addTable();
                \$row = \$table->addRow();

                \$row->addCell(8000)->addImage(storage_path('img/niu_logo_alt.jpg'), [
                    'width' => 160,
                    'height' => 40,
                    'alignment' => Jc::START,
                    'alt' => 'Northern Illinois University logo'
                ]);

                \$rightside = \$row->addCell(8000);

                \$rightside->addText(\$title, 
                ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => true], 
                ['alignment' => Jc::END]
                );

                \$rightside->addText('Printed: ' . date("m/d/Y"), 
                ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false], 
                ['alignment' => Jc::END]
                );

                \$header->addTextBreak();

                // footer
                \$footer = \$section->addFooter();
                \$footer->addPreserveText('Page {PAGE}/{NUMPAGES}', 
                ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => true], 
                ['alignment' => Jc::CENTER]
                );
            }

            // add styling to the doc
            private static function addStyling(\PhpOffice\PhpWord\PhpWord \$doc) {

                \$doc->addFontStyle( // normal style
                    'FMLstyle',
                    ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false]
                );

                \$doc->addFontStyle( // bolded
                    'FMLstyleB',
                    ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => true]
                );

                \$doc->addFontStyle( // bolded
                    'boxstyleB',
                    ['name' => 'Arial', 'size' => 9, 'color' => '000000', 'bold' => true]
                );

                \$doc->addFontStyle( // B + U => underlined
                    'FMLstyleBU', 
                    ['name' => 'Arial', 'size' => 11, 'color' => '000000', 'bold' => true, 'underline' => 'single']
                );

                \$doc->addFontStyle( // U => underlined
                    'FMLstyleU', 
                    ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => false, 'underline' => 'single']
                );

                \$doc->addFontStyle( // L => 'large' 14 pt font
                    'FMLstyleL',
                    ['name' => 'Arial', 'size' => 14, 'color' => '000000', 'bold' => false]
                );

                \$doc->addFontStyle( // S => 'medium' 10 pt font
                    'FMLstyleM', 
                    ['name' => 'Arial', 'size' => 10, 'color' => '000000', 'bold' => false]
                );

                \$doc->addFontStyle( // S => 'small' 9 pt font
                    'FMLstyleS', 
                    ['name' => 'Arial', 'size' => 8, 'color' => '000000', 'bold' => false]
                );

                \$doc->addFontStyle( // cramped style
                    'FMLstyleCramped',
                    ['name' => 'Arial', 'size' => 11, 'color' => '000000', 'bold' => false]
                );

                // h1
                \$doc->addTitleStyle(
                    1, 
                    ['name' => 'Arial', 'size' => 12, 'color' => '000000', 'bold' => true, 'italic' => true],
                    ['alignment' => Jc::CENTER] 
                );

                // h2
                \$doc->addTitleStyle(
                    2, 
                    ['name' => 'Arial', 'size' => 13, 'color' => '000000', 'bold' => true],
                    ['alignment' => Jc::START]
                );

                // h3
                \$doc->addTitleStyle(
                    3, 
                    ['name' => 'Arial', 'size' => 11, 'color' => '000000', 'bold' => true, 'underline' => 'single'],
                    ['alignment' => Jc::START]
                );

                \$doc->addParagraphStyle('crammedL', [
                    'lineHeight' => 0.8,
                    'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::START
                ]);

                \$doc->addParagraphStyle('crammedC', [
                    'lineHeight' => 0.8,
                    'alignment'  => Jc::CENTER,
                ]);

                \$doc->addParagraphStyle('crammedR', [
                    'lineHeight' => 0.8,
                    'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::END
                ]);
            }
            
            // MUSIC BUILDING -> MB, or any building/room
            private static function abbreviate(\$string)
            {
                \$string = strtoupper(\$string);
                
                if (str_contains(\$string, "MEMORIAL LIBRARY")) return "FO";
                if (str_contains(\$string, "MUSIC")) return "MB";

                // whitespace
                \$words = preg_split('/\s+/', trim(\$string));

                // every first letter
                \$initials = array_map(fn(\$w) => strtoupper(\$w[0]), \$words);

                // one string
                return implode('', \$initials);
            }

            private static function formatPhone(\$number)
            {
                // only digits
                \$digits = preg_replace('/\D/', '', \$number);

                // remove US num
                if (strlen(\$digits) === 11 && \$digits[0] === '1') {
                    \$digits = substr(\$digits, 1);
                }

                // ###-###-###
                \$output = substr(\$digits, 0, 3) . '-' . substr(\$digits, 3, 3) . '-' . substr(\$digits, 6, 4);
                
                return \$output;
            }



        }
        PHP;

        File::put($path, $boilerplate);
        
        $this->info("docx service template controller created");
    }
}
