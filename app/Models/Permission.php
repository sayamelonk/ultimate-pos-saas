<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];

    /**
     * Relationship: Permission belongs to many Roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Static Helper: Get semua modules
     */
    public static function getModules(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'pos' => 'POS',
            'orders' => 'Orders',
            'products' => 'Products',
            'categories' => 'Categories',
            'inventory' => 'Inventory',
            'tables' => 'Tables',
            'kitchen' => 'Kitchen',
            'reports' => 'Reports',
            'outlets' => 'Outlets',
            'users' => 'Users',
            'roles' => 'Roles & Permissions',
            'settings' => 'Settings',
        ];
    }
}
