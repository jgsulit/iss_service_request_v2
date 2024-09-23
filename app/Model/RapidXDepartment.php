<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
Use App\Model\User;

class RapidXDepartment extends Model
{
    protected $connection = 'mysql_rapidx';
    protected $table = 'departments';

    public function user_info() {
        return $this->hasOne(User::class, 'user_id', 'id');
    }
}
