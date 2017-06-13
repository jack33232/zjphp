<?php
namespace ZJPHP\Model\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use ZJPHP\Base\ZJPHP;

class GroupType extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function groups()
    {
        return $this->hasMany('ZJPHP\\Model\\Dataset\\Group');
    }

    public function defaultActionPermissionSetting()
    {
        return $this->hasOne('ZJPHP\\Model\\Dataset\\DefaultPermissionSetting')->where('type', 'action');
    }

    public function defaultResourcePermissionSetting()
    {
        return $this->hasOne('ZJPHP\\Model\\Dataset\\DefaultPermissionSetting')->where('type', 'resource');
    }
}
