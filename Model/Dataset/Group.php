<?php
namespace ZJPHP\Model\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use ZJPHP\Base\ZJPHP;

class Group extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = ['name'];

    public function actionPermissions()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\ActionPermission', 'action_permission_group', 'group_id')->withTimestamps();
    }

    public function resourcePermissions()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\ResourcePermission', 'group_resource_permission', 'group_id')->withTimestamps()->withPivot('rules');
    }

    public function children()
    {
        return $this->hasMany('ZJPHP\\Model\\Dataset\\Group', 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo('ZJPHP\\Model\\Dataset\\Group', 'parent_id');
    }

    public function groupType()
    {
        return $this->belongsTo('ZJPHP\\Model\\Dataset\\GroupType');
    }

    public function authorization()
    {
        return $this->hasMany('ZJPHP\\Model\\Dataset\\Authorization');
    }

    public function ownedBy()
    {
        return $this->hasMany('ZJPHP\\Model\\Dataset\\Authorization', 'authorized_group_id');
    }

    public function users()
    {
        return $this->hasMany('ZJPHP\\Model\\Dataset\\User', 'group_id');
    }
}
