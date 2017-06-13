<?php
namespace ZJPHP\Model\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use ZJPHP\Base\ZJPHP;
use ZJPHP\Model\Scope\AuthScope;

class RestrictDataset extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AuthScope);
    }
}
