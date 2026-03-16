<?php

use Livewire\Component;
use App\Models\CountryOfBirth;
use Carbon\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Facades\Pipeline;

new class extends Component
{

    public $country;
    public $editID = null;

    public function getCountriesProperty() {   
        return CountryOfBirth::latest()->get();
    }   

    public function store() {

        $this->validate([
            'country' => 'required|string'
        ]);
        
        if (!self::validCountry()) return;

        $item = new CountryOfBirth([
            'country' => $this->country
        ]);
        
        $item->save();
    }
    
    public function update() {
        
        $item = CountryOfBirth::findOrFail($this->editID);

        $this->validate([
            'country' => 'required|string'
        ]);

        if (!self::validCountry()) return;

        $item->fill([
            'country' => $this->country
        ]);
        
        abort_unless($item->isDirty(), 400, 'No changes');
        abort_unless($item->save(), 500, 'Save failed');
        $this->editID = null;       
        $this->reset(['country']);

    }

    
    public function destroy($id) {
        CountryOfBirth::findOrFail($id)->delete();
    }
    
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

    public function validCountry() {

        $path = base_path('vendor\stefangabos\world_countries\data\countries\en\countries.php');
        $countries = include $path;    

        $countriesarray = array_column($countries, 'name');
        $countriesarray = array_map('mb_strtolower', $countriesarray);
        $countryinput = strtolower($this->country);
        
        if (!in_array($countryinput, $countriesarray)) {
            $this->dispatch('show-alert', message: 'Invalid country selected!');
            return false;
        }

        return true;

    }

    public function edit($id) {
        $item = CountryOfBirth::findOrFail($id);
        $this->editID = $id;
        $this->country = $item->country;
    }

    public function cancelEdit() {
        $this->editID = null;
    }



    /*
        • one input field => country of birth
        • persist in sqllite database 
        • CRUD + export endpoints. 

        -> store ✓
        -> read ✓
        -> update ✓
        -> delete ✓
        -> export sorta? i have no idea what popping up on the screen but hey it works.

        added via composer livewire/livewire
        added via composer spatie/laravel-permission
        added via composer spatie/simple-excel
        added via composer livewire/volt
        
    */

};
?>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: top; min-height: 100vh; padding: 20px;">
    
    <h1>Country of Birth CRUD</h1>

    <form wire:submit.prevent="store" style="margin-bottom: 2rem;">
        <input wire:model="country" type="text" placeholder="Add country..." style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
        <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Enter
        </button>

        <button 
        type="button"
        wire:click="export" 
        wire:loading.attr="disabled"
        style="padding: 10px 20px; background: #00b8bb; color: white; border: none; border-radius: 5px; cursor: pointer;"
        >
            <span wire:loading.remove>Export</span>
            <span wire:loading>Generating File...</span>
        </button>
    </form>

    <div style="width: 100%; max-width: 400px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="text-align: center; margin-top: 0;">Saved Entries</h3>
        
        <div style="max-height: 300px; overflow-y: auto;">
             @foreach($this->countries as $item)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 7px; border-bottom: 1px solid #eee;">
                    @if($editID !== $item->id)
                        <span>{{ $item->country }}</span>
                        <div>
                            <button wire:click="edit({{ $item->id }})" style="color: #4da6ff; border: none; background: none; cursor: pointer;">Edit</button>
                            <button wire:click="destroy({{ $item->id }})" style="color: #ff4d4d; border: none; background: none; cursor: pointer;">Delete</button>
                        </div>
                    @else
                        <form wire:submit.prevent="update" style="display: flex; gap: 5px; width: 100%;">
                            <input wire:model="country" type="text" placeholder="Edit country..." style="flex-grow: 1; padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                            
                            <button type="submit" style="padding: 5px 15px; background: #00bb00; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                Enter
                            </button>
                            
                            <button type="button" wire:click="$set('editID', null)" style="color: #999; border: none; background: none; cursor: pointer;">
                                Cancel
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div> 
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-alert', (event) => {
            alert(event.message);
        });
        });
    </script>

</div>
