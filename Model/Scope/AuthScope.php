<?php
namespace ZJPHP\Model\Scope;

use ZJPHP\Base\ZJPHP;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AuthScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $auth = ZJPHP::$app->get('auth');

        $modelClass = get_class($model);
        $rules = $auth->getResourcePermission($modelClass);

        function buildQuery($query, $rules)
        {
            // TBD more complex rules
            foreach ($rules as $name => $rule) {
                switch ($name) {
                    case 'where':
                    case 'orWhere':
                    case 'whereBetween':
                    case 'whereNotBetween':
                    case 'whereIn':
                    case 'whereNotIn':
                    case 'whereNull':
                    case 'whereNotNull':
                        $query = call_user_func_array(array($query, $name), $rule);
                        break;
                }
            }

            return $query;
        }

        if (empty($rules)) {
            return $builder->whereRaw('false');
        } else {
            return buildQuery($builder, $rules);
        }
    }
}
