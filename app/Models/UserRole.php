<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'role',
        'target_entity',
        'entity_id',
        'role_status',
    ];

    protected $casts = [
        'entity_id' => 'integer',
    ];

    // Role constants
    const ROLE_GOVERNOR = 'Governor';
    const ROLE_SYSTEM_ADMIN = 'System Admin';
    const ROLE_SECTOR_HEAD = 'Sector Head';
    const ROLE_SECTOR_ADMIN = 'Sector Admin'; // Deprecated - use ROLE_DATA_ADMIN
    const ROLE_DATA_ADMIN = 'Data Admin';
    const ROLE_DELIVERY_DEPARTMENT = 'Delivery Department'; // Deprecated - use new delivery unit roles
    const ROLE_COORDINATOR = 'Coordinator';
    const ROLE_DEPUTY_COORDINATOR = 'Deputy Coordinator';
    const ROLE_FACILITATOR = 'Facilitator';

    // Target entity constants
    const ENTITY_SYSTEM = 'System';
    const ENTITY_STATE = 'State';
    const ENTITY_SECTOR = 'Sector';
    const ENTITY_PROJECT = 'Project';
    const ENTITY_DELIVERABLE = 'Deliverable';

    // Role status constants
    const STATUS_ACTIVE = 'Active';
    const STATUS_REVOKED = 'Revoked';

    /**
     * Get the user that owns this role
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sector associated with this role (if applicable)
     * For single-sector roles (Sector Head, Data Admin)
     */
    public function sector()
    {
        if ($this->target_entity === self::ENTITY_SECTOR && $this->role !== self::ROLE_FACILITATOR) {
            return $this->belongsTo(Sector::class, 'entity_id');
        }
        return null;
    }

    /**
     * Get all sectors assigned to this facilitator role
     */
    public function facilitatorSectors()
    {
        return $this->hasMany(FacilitatorSector::class);
    }

    /**
     * Get sectors for this role (works for both single and multiple sectors)
     */
    public function sectors()
    {
        if ($this->role === self::ROLE_FACILITATOR) {
            return $this->hasManyThrough(Sector::class, FacilitatorSector::class, 'user_role_id', 'id', 'id', 'sector_id');
        }
        // For single-sector roles, return a collection with one sector
        if ($this->target_entity === self::ENTITY_SECTOR && $this->entity_id > 0) {
            $sector = Sector::find($this->entity_id);
            return $sector ? collect([$sector]) : collect();
        }
        return collect();
    }

    /**
     * Scope to get only active roles
     */
    public function scopeActive($query)
    {
        return $query->where('role_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get only revoked roles
     */
    public function scopeRevoked($query)
    {
        return $query->where('role_status', self::STATUS_REVOKED);
    }

    /**
     * Check if the role is active
     */
    public function isActive()
    {
        return $this->role_status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the role is revoked
     */
    public function isRevoked()
    {
        return $this->role_status === self::STATUS_REVOKED;
    }

    /**
     * Revoke this role
     */
    public function revoke()
    {
        $this->role_status = self::STATUS_REVOKED;
        return $this->save();
    }

    /**
     * Activate this role
     */
    public function activate()
    {
        $this->role_status = self::STATUS_ACTIVE;
        return $this->save();
    }

    /**
     * Get role to target entity mapping
     */
    public static function getRoleToEntityMapping()
    {
        return [
            self::ROLE_GOVERNOR => self::ENTITY_STATE,
            self::ROLE_SYSTEM_ADMIN => self::ENTITY_SYSTEM,
            self::ROLE_SECTOR_HEAD => self::ENTITY_SECTOR,
            self::ROLE_SECTOR_ADMIN => self::ENTITY_SECTOR, // Deprecated
            self::ROLE_DATA_ADMIN => self::ENTITY_SECTOR,
            self::ROLE_DELIVERY_DEPARTMENT => self::ENTITY_DELIVERABLE, // Deprecated
            self::ROLE_COORDINATOR => self::ENTITY_DELIVERABLE,
            self::ROLE_DEPUTY_COORDINATOR => self::ENTITY_DELIVERABLE,
            self::ROLE_FACILITATOR => self::ENTITY_SECTOR, // Facilitators are assigned to specific sectors
        ];
    }

    /**
     * Check if this role is a delivery unit role (Coordinator, Deputy Coordinator, or Facilitator)
     */
    public function isDeliveryUnitRole()
    {
        return in_array($this->role, [
            self::ROLE_COORDINATOR,
            self::ROLE_DEPUTY_COORDINATOR,
            self::ROLE_FACILITATOR,
            self::ROLE_DELIVERY_DEPARTMENT, // For backward compatibility
        ]);
    }

    /**
     * Check if this role can access all sectors (Coordinator or Deputy Coordinator)
     */
    public function canAccessAllSectors()
    {
        return in_array($this->role, [
            self::ROLE_COORDINATOR,
            self::ROLE_DEPUTY_COORDINATOR,
            self::ROLE_DELIVERY_DEPARTMENT, // For backward compatibility
        ]);
    }

    /**
     * Check if this role is restricted to assigned sectors (Facilitator)
     */
    public function isRestrictedToAssignedSectors()
    {
        return $this->role === self::ROLE_FACILITATOR;
    }
}
