<?php

namespace App\Providers;

use App\Models\Admin\Apply;
use App\Models\GoodsSpu;
use App\Observers\GoodsSpuObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Services\LengthAwarePaginatorService;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Validation\Validator as Validators;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('Illuminate\Pagination\LengthAwarePaginator', function ($app, $options) {
            return new LengthAwarePaginatorService($options['items'], $options['total'], $options['perPage'], $options['currentPage'], $options['options']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        error_reporting(E_ERROR);
        GoodsSpu::observe(GoodsSpuObserver::class);
        Validator::extend('mobile', function ($attribute, $value, $parameters, Validators $validator) {
            return $validator->validateRegex(
                $attribute,
                $value,
                ['/^(13[0-9]|19[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9]|16[0-9]|17[0-9]|18[0-9])\d{8}$/']
            );
            // return true;
        });
        // 基于关联关系排序实现
        Builder::macro(
            'orderByWith',
            function ($relation, $column, $direction = 'asc',$directionType ='orderBy'): Builder {
                /** @var Builder $this */
                if (is_string($relation)) {
                    $relation = $this->getRelationWithoutConstraints($relation);
                }
                return $this->orderBy(
                    $relation->getRelationExistenceQuery(
                        $relation->getRelated()->newQueryWithoutRelationships(),
                        $this,
                        $column
                    ),
                    $direction
                );
            }
        );
        $sql_debug = env('APP_DEBUG');
        if ($sql_debug) {
            DB::listen(function ($sql) {
                foreach ($sql->bindings as $i => $binding) {
                    if ($binding instanceof \DateTime) {
                        $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                    } else {
                        if (is_string($binding)) {
                            $sql->bindings[$i] = "'$binding'";
                        }
                    }
                }
                $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);
                $query = vsprintf($query, $sql->bindings);
                Log::info($query);
            });
        }
    }
}
