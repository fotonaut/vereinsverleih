<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Club extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'email', 'phone', 'street', 'zip', 'city', 'website'];

    protected static function booted(): void
    {
        static::creating(function (Club $club) {
            if (! $club->slug) {
                $base = Str::slug($club->name) ?: 'verein';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $club->slug = $slug;
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
