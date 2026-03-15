<?php

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class SearchFor
{
    public function __construct(protected Request $request)
    {
    }

    public function handle(Builder $builder, \Closure $next)
    {
        //URL syntax: /users?q=searchterm&s=column1,column2
        //SQL: column1 LIKE '%searchterm%' OR column2 LIKE '%searchterm%' 
        $request = $this->request;
        return $next($builder)
            ->when(
                $request->has('q') && $request->q != '' && $request->has('s') && $request->s != '',
                function ($query) use ($request) {
                    //closure to group OR conditions
                    $query->where(function ($q) use ($request) {
                        $columnArr = explode(",", $request->s);
                        foreach ($columnArr as $column) {
                            $rsArr = explode(".", $column);
                            if (count($rsArr) === 1) {
                                //column part of model
                                $q->orWhere($column, 'REGEXP', $request->q);
                            } else {
                                //column part of relationship (hasOne, belongsTo) defined by expand prefix
                                //syntax: expand.column ...example: user.last_name
                                $model = $rsArr[0];
                                $col = $rsArr[1];
                                $q->orWhereHas($model, fn (Builder $relationship) => $relationship->where($col, 'REGEXP', $request->q));
                            }
                        }
                    });
                }
            );
    }
}
