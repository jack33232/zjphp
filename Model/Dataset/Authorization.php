<?php
namespace ZJPHP\Model\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use ZJPHP\Base\ZJPHP;

class Authorization extends Model
{
    public function authorizedGroup()
    {
        return $this->belongsTo('ZJPHP\\Model\\Dataset\\Group', 'authorized_group_id');
    }
}
