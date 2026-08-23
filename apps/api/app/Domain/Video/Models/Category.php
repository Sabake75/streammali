<?php

namespace App\Domain\Video\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'label'])]
class Category extends Model
{
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
