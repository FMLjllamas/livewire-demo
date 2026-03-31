<?php
/*
    -> one input field => country of birth ✓
    -> persist in sqllite database ✓
    -> CRUD + export endpoints ✓

    -> store ✓
    -> read ✓
    -> update ✓
    -> delete ✓

    -> turn the blade component into a controller ✓
    -> create web routes instead of api routes ✓
    -> reference the web routes in birthpage.blade ✓

    -> redo
        -> list ✓
        -> find by ID ✓
        -> store ✓
        -> delete ✓
        -> update ✓
    -> add tailwind styles in birthpage.blade ✓

    added via composer livewire/livewire
    added via composer livewire/volt
    added via composer spatie/laravel-permission
    added via composer spatie/simple-excel
    
    you still need to create controllers and put your controller logic in there (NOT in the blade view)
    so instead of using the api routes (apiResource) you create web routes that have additional methods
    Even though it's a test app, I would like you to fix those two issues (move logic into controller, switch to tailwind classes).
 
*/
?>

<!DOCTYPE html>
<html>
    <head>
        <style>
            body {
                font-family: "Arial", Tahoma, Geneva, Verdana, sans-serif;
                font-weight: 200;
            }
        </style>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>livewire CRUD</title>
    </head>
<body>

<style>
    .wrap {
        max-width: 600px;
        margin: 30px auto;
        padding: 20px;
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

    }

    .top-bar {
        display: flex;
        justify-content: space-between; 
        align-items: center;
    }

    .title1 {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #111;
        display: flex;
        justify-content: center;
    }

    .title2 {
        font-size: 28px;
        font-weight: bold;
        color: #222;
        justify-content: left;
        display: flex;
        margin-bottom: 20px;
    }

    .list {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .item {

        width: 200px;
        display: flex;
        
        justify-content: center;
        background: white;

        border-radius: 20px;
        transition: 0.1s ease;
    }

    .item:not(:last-child) {
        margin-bottom: 10px;
    }

    .item:hover {
        background: #eeefff;
        border-color: #c9c9c9;
    }

    .empty {
        color: #777;
        font-style: italic;
    }

    .row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 300px;
        padding: 10px 14px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background: white;
    }

    .pages {
        display: flex;
        justify-content: center;
        margin-top: 10px;
    }

    .pages nav > div {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 16px;
    }

    .pages a,
    .pages span {
        font-size: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 10px;
        height: 20px;
        padding: 0 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        background: white;
    }

    .pages a:hover {
        background: #f5f5f5;
    }

    .pages .relative.inline-flex.items-center.px-4.py-2.text-sm.font-medium {
        border-radius: 8px;
    }

    .popup {
        display: flex; 
        align-items: center;
        justify-content: center;

        padding: 15px 10px;
        margin: 20px auto;
        width: 200px;
        background: #dcffee;

        border: 1px solid #212121;
        border-radius: 20px;
        
        transition: 1s ease;
    }

    .searchidbar {
        width: 70px; 
        padding: 6px; 
        border: 1px solid #ccc; 
        border-radius: 6px;
    }

    .updatebar {
        width: 100px; 
        padding: 6px; 
        border: 1px solid #f8e1ff; 
        border-radius: 6px;
    }

    .findbutton {    
        padding: 6px 12px; 
        border: none; 
        border-radius: 6px; 
        background: #333; 
        color: white; 
        cursor: pointer;
    }

    .addcountrybar {
        width: 200px; 
        padding: 6px; 
        border: 1px solid #ccc; 
        border-radius: 6px;
    }

    .addbutton {
        padding: 6px 12px; 
        border: none; 
        border-radius: 6px; 
        background: #2da015; 
        color: white; 
        cursor: pointer;
    }

    .deletebutton {
        margin: auto 5px auto 5px;
        padding: 4px 8px; 
        border-radius: 6px; 
        background: #ff5656; 
        color: white; 
        cursor: pointer;
        font-size: 14px;
    } 

    .updatebutton {
        margin: auto 5px auto 5px;
        padding: 4px 8px; 
        border-radius: 6px; 
        background: #ee9aff; 
        color: white; 
        cursor: pointer;
        font-size: 14px;
    } 


</style>

@if (session('success'))
    <p class="popup" style="color: green; background: #dcffee;" >{{ session('success') }}</p>
@endif

@if (session('error'))
    <p class="popup" style="color: red; background: #ffc6c6;">{{ session('error') }}</p>
@endif

<div>
    <h1 class="title1">country of birth CRUD!</h1>
</div>

<div class="wrap">

    <div class="top-bar">

        <h2 class="title2">countries</h2>

        <form method="GET" action="{{ route('birthpage.show') }}">

            <input type="number" class="searchidbar" name="id" placeholder="get by id..." min="0">
            <button type="submit" class="findbutton">find</button>
        
        </form>

        <form method="POST" action="{{ route('birthpage.store') }}">
            @csrf

            <input type="text" name="country" placeholder="add a country..." class="addcountrybar">

            <button type="submit" class="addbutton">add</button>
        </form>

        <form method="GET" action="{{ route('birthpage.export') }}">

            <button type="submit" style="color: black; background: #00920c;" class="findbutton">excel</button>
        
        </form>

    </div>


    @if($searchedcountry)
        <div class="popup">
            {{ $searchedcountry }}
        </div>
    @endif

    @if($changedcountry !== null)
        <div class="popup">
            <form method="POST" action="{{ route('birthpage.update') }}" style="display: inline;">
                @csrf
                @method('PUT')

                <input type="hidden" name="id" value="{{ $changedcountry->id }}">

                <input type="text" name="country" value="{{ $changedcountry->country }}" class="updatebar">
                
                <button type="submit" class="addbutton">update</button>

            </form>
        </div>
    @endif


    @if ($countries->count())
    
        <ul class="list">
            @foreach ($countries as $country)
                <li class="row">

                    <span class="item">{{ $country->country }}</span>

                    <form method="POST" action="{{ route('birthpage.delete') }}" style="display: inline;">
                        @csrf
                        @method('DELETE')

                        <input type="hidden" name="id" value="{{ $country->id }}">
                        
                        <button type="submit" class="deletebutton">
                            delete
                        </button>
                    </form>
                    
                    <form method="GET" action="{{ route('birthpage.index') }}" style="display: inline;">

                        <input type="hidden" name="edit_id" value="{{ $country->id }}">
                        <button type="submit" class="updatebutton">update</button>

                    </form>

                </li>
            @endforeach
        </ul>
    @else
        <p class="empty">No countries found.</p>
    @endif




    <div class="pages">
        {{ $countries->links() }}
    </div>
</div>



</body>
</html>