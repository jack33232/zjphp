<?php
namespace ZJPHP\Model\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use ZJPHP\Base\ZJPHP;

class User extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = ['username', 'firstname', 'lastname', 'password', 'group_id', 'phone', 'title', 'gender'];

    public function setPasswordAttribute($value)
    {
        $security = ZJPHP::$app->get('security');
        $this->attributes['password'] = $security->generatePasswordHash($value);
    }

    public function group()
    {
        return $this->belongsTo('ZJPHP\\Model\\Dataset\\Group');
    }

    public function authorizedActionPermissions()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\ActionPermission')->wherePivot('type', 'authorize')->withTimestamps();
    }

    public function deprivedActionPermissions()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\ActionPermission')->wherePivot('type', 'deprive')->withTimestamps();
    }

    public function authorizedResourcePermissions()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\ResourcePermission')->withPivot('rules')->wherePivot('type', 'authorize')->withTimestamps();
    }

    public function deprivedResourcePermissions()
    {
        return $this->belongsToMany('ZJPHP\\Model\\Dataset\\ResourcePermission')->withPivot('rules')->wherePivot('type', 'deprive')->withTimestamps();
    }
}
