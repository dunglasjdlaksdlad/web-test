<?php

namespace App\Models\Dashboard_And_Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileManager extends Model
{
        use SoftDeletes;
        protected $fillable = ['name', 'uuid', 'created_by'];
}
