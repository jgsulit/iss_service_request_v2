<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SystemOnePhoneDirectory extends Model
{
    protected $table = 'tbl_phone_directory';
    protected $connection = 'mysql_systemone_phone_directory';
}
