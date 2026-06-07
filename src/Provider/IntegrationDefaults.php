<?php

namespace Pixel\GDPRBundle\Provider;

final class IntegrationDefaults
{
    public const TYPE_PRECONFIGURED = 'preconfigured';
    public const TYPE_CUSTOM_INLINE = 'custom_inline';
    public const TYPE_CUSTOM_EXTERNAL = 'custom_external';
    public const TYPE_MANUAL = 'manual';

    public const TYPES = [
        self::TYPE_PRECONFIGURED,
        self::TYPE_CUSTOM_INLINE,
        self::TYPE_CUSTOM_EXTERNAL,
        self::TYPE_MANUAL,
    ];

    public const PROVIDERS = ['gtag', 'googletagmanager', 'googleads', 'bingads', 'facebookpixel'];

    public const CONSENT_SIGNALS = [
        'analytics_storage',
        'ad_storage',
        'ad_user_data',
        'ad_personalization',
        'functionality_storage',
        'personalization_storage',
        'security_storage',
    ];

    /**
     * @return string[]
     */
    public static function categoriesForProvider(string $provider): array
    {
        return match ($provider) {
            'gtag' => ['analytics_storage'],
            'googletagmanager' => ['analytics_storage', 'ad_storage'],
            'googleads' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
            'bingads' => ['ad_storage'],
            'facebookpixel' => ['ad_storage'],
            default => [],
        };
    }

    public static function displayName(string $provider): string
    {
        return match ($provider) {
            'gtag' => 'Google Analytics',
            'googletagmanager' => 'Google Tag Manager',
            'googleads' => 'Google Ads',
            'bingads' => 'Bing Ads',
            'facebookpixel' => 'Facebook Pixel',
            default => $provider,
        };
    }
}
