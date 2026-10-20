<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
class Region extends Model
{
    use HasRoles;
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function centers(): HasMany
    {
        return $this->hasMany(Center::class);
    }
     public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}