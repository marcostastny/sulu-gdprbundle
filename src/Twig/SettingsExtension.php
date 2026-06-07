<?php

namespace Pixel\GDPRBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\GDPRBundle\Entity\Integration;
use Pixel\GDPRBundle\Entity\Setting;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Environment $environment,
        private RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction("gdpr_settings", [$this, "gdprSettings"]),
            new TwigFunction("gdpr_script", [$this, "gdprScript"], [
                "is_safe" => ["html"],
            ]),
        ];
    }

    public function gdprSettings(): Setting
    {
        return $this->entityManager->getRepository(Setting::class)->findOneBy([]) ?? new Setting();
    }

    public function gdprScript(): ?string
    {
        $setting = $this->entityManager->getRepository(Setting::class)->findOneBy([]);
        if (null === $setting) {
            $setting = new Setting();
            $setting->setUseCookieHandling(false);
        }
        if (!$setting->getUseCookieHandling()) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $locale = \explode('_', $request ? $request->getLocale() : 'en')[0];

        $integrations = [];
        foreach (
            $this->entityManager->getRepository(Integration::class)->findBy(['enabled' => true], ['position' => 'ASC'])
            as $integration
        ) {
            $integrations[] = $integration->toFrontendArray($locale);
        }

        return $this->environment->render("@GDPR/twig/scripts.html.twig", [
            "setting" => $setting,
            "integrations" => $integrations,
        ]);
    }
}
