<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Developer = 'developer';
    case SeoExpert = 'seo_expert';
    case SecurityAnalyst = 'security_analyst';
    case Client = 'client';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Developer => 'Developer',
            self::SeoExpert => 'SEO Expert',
            self::SecurityAnalyst => 'Security Analyst',
            self::Client => 'Client',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
