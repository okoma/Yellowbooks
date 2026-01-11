<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case BUSINESS_OWNER = 'business_owner';
    case BRANCH_MANAGER = 'branch_manager';
    case CUSTOMER = 'customer';

    /**
     * Get human-readable label for the role
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::MODERATOR => 'Moderator',
            self::BUSINESS_OWNER => 'Business Owner',
            self::BRANCH_MANAGER => 'Branch Manager',
            self::CUSTOMER => 'Customer',
        };
    }

    /**
     * Get description of the role
     */
    public function description(): string
    {
        return match($this) {
            self::ADMIN => 'Full system access',
            self::MODERATOR => 'Content moderation only',
            self::BUSINESS_OWNER => 'Owns and manages businesses',
            self::BRANCH_MANAGER => 'Manages specific branches',
            self::CUSTOMER => 'Regular user',
        };
    }

    /**
     * Get all roles as array (for dropdowns)
     */
    public static function toArray(): array
    {
        return [
            self::ADMIN->value => self::ADMIN->label(),
            self::MODERATOR->value => self::MODERATOR->label(),
            self::BUSINESS_OWNER->value => self::BUSINESS_OWNER->label(),
            self::BRANCH_MANAGER->value => self::BRANCH_MANAGER->label(),
            self::CUSTOMER->value => self::CUSTOMER->label(),
        ];
    }
}