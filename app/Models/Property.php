<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'alamat', 'nama_pemilik'])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
