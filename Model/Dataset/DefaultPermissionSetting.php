<?php
namespace ZJPHP\Model\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use ZJPHP\Base\ZJPHP;

class DefaultPermissionSetting extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function groupType()
    {
        return $this->belongsTo('ZJPHP\\Model\\Dataset\\GroupType');
    }
}
