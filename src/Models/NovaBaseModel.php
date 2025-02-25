<?php

namespace DrBalcony\NovaCommon\Models;

use DrBalcony\NovaCommon\Traits\NovaModelTrait;
use Illuminate\Database\Eloquent\Model;

abstract class NovaBaseModel extends Model
{
    use NovaModelTrait;
}