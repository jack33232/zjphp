<?php
namespace ZJPHP\Model\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use ZJPHP\Base\ZJPHP;

class ResourcePermission extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];


    public function groups()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\Group')->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\User')->withTimestamps();
    }
}
