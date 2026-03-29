<?php

namespace App\Http\Controllers;

use App\Models\CountryOfBirth;

use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

use function PHPUnit\Framework\isNull;

class CountryOfBirthController extends Controller
{

    public function index(Request $request) {
 
        $pipeline = [
            \App\Filters\SearchFor::class,
            \App\Filters\OrderBy::class
        ];

        try {
        
            $countries = Pipeline::send(CountryOfBirth::query())
            ->through($pipeline)
            ->thenReturn()
            ->orderBy('id', 'DESC')
            ->paginate(10);

            $searchedcountry = CountryOfBirth::find($request->id)?->country;
            
            $editedcountry = $request->filled('edit_id')
            ? CountryOfBirth::find($request->edit_id)
            : null;

        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return redirect()->back()->with('error', 'could not load countries');
        
        }

        return view(
            'birthpage', 
            [
                'countries' => $countries,
                'searchedcountry' => $searchedcountry,
                'changedcountry' => $editedcountry
            ]
        );
    }

    public function show(Request $request) {
        return redirect()->route('birthpage.index', [
            'id' => $request->id
        ]);
    }   

    public function store(Request $request) {

        $request->validate([
            'country' => 'required|string'
        ]);
        
        if (!self::validCountry($request->country)) 
            return redirect()->route('birthpage.index')->with('error', 'country does not exist');

        $item = new CountryOfBirth([
            'country' => $request->country
        ]);
        
        $item->save();

        return redirect()->route('birthpage.index')->with('success', 'country added');
    }
    
    public function update(Request $request) {
        
        $request->validate([
            'id' => 'required|integer',
            'country' => 'required|string'
        ]);

        $item = CountryOfBirth::find($request->id);

        if (!$item) {
            return redirect()->route('birthpage.index')->with('error', 'country not found');
        }

        if (!self::validCountry($request->country)) {
            return redirect()->route('birthpage.index')->with('error', 'invalid country');
        }

        $item->fill([
            'country' => $request->country
        ]);
        
        if (!$item->isDirty()) {
            return redirect()->route('birthpage.index')->with('error', 'no changes');
        }

        if (!$item->save()) {
            return redirect()->route('birthpage.index')->with('error', 'save failed');
        }

        return redirect()->route('birthpage.index')->with('success', 'country updated');


    }

    public function destroy(Request $request) {

        $country = CountryOfBirth::find($request->id);

        if (!$country) {
            return redirect()->route('birthpage.index')->with('error', 'country not found');
        }

        $country->delete();

        return redirect()->route('birthpage.index')->with('success', 'country deleted');
    
    }

/*
    public function export() {
        
        $pipeline = [
            \App\Filters\SearchFor::class,
            \App\Filters\OrderBy::class
        ];

        $rows = [];

        try {

            Pipeline::send( CountryOfBirth::query() )
            ->through($pipeline)
            ->thenReturn()
            ->chunk(2500, function ($items) use (&$rows) {

                foreach ($items->toArray() as $item) {
                    $rows[] = $item;
                }
            });

        } catch (\Illuminate\Database\QueryException $e) {

            return response()->json([
                'error' => [true],
                'message' => 'Invalid query'

            ]);
        }
        
        return SimpleExcelWriter::streamDownload( (new CountryOfBirth)->getTable() . Carbon::now() . ".xlsx" )->addRows($rows)->toBrowser();
    }
*/

    private function validCountry($input) {

        $path = base_path('vendor\stefangabos\world_countries\data\countries\en\countries.php');
        $countries = include $path;    

        $countriesarray = array_column($countries, 'name');
        $countriesarray = array_map('mb_strtolower', $countriesarray);
        $countryinput = strtolower($input);
        
        foreach ($countriesarray as $country) {
            if ($country === $countryinput) {
                return true;
            }

            if (str_starts_with($country, $countryinput . ',')) {
                return true;
            }
        }

        return false;

    }

}
