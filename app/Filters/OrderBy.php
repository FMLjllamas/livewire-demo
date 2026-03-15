<?php

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class OrderBy
{
    public function __construct(protected Request $request)
    {
    }

    public function handle(Builder $builder, \Closure $next)
    {
        //URL syntax: http://myapp.com/users?sort=name,-email
        //SQL: ORDER BY name ASC, email DESC
        $request = $this->request;
        return $next($builder)
            ->when(
                $request->has('sort') && $request->sort != '',
                function ($query) use ($request) {
                    $sortArr = explode(",", $request->sort);
                    foreach ($sortArr as $columnRaw) {
                        if (str_starts_with($columnRaw, '-')) {
                            $columnWithOrder = [ltrim($columnRaw, '-'), 'DESC'];
                        } else {
                            $columnWithOrder = [$columnRaw, 'ASC'];
                        }
                        $query->orderBy(...$columnWithOrder);
                    }
                }
            );
    }
}
