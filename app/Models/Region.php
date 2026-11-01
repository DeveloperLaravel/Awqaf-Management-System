<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Region extends Model
{
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

   /**
     * المراكز التابعة للمنطقة.
     */
    public function centers(): HasMany
    {
        return $this->hasMany(Center::class, 'region_id');
    }

    /**
     * المستخدمون المرتبطون مباشرة بالمنطقة.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'region_id');
    }
}