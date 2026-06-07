<?php

namespace Pixel\GDPRBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

#[ORM\Entity]
#[ORM\Table(name: 'gdpr_integration')]
class Integration implements AuditableInterface
{
    use AuditableTrait;

    public const RESOURCE_KEY = 'gdpr_integrations';
    public const LIST_KEY = 'gdpr_integrations';
    public const FORM_KEY = 'gdpr_integration';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'service_key', type: 'string', length: 100, unique: true)]
    private string $serviceKey = '';

    #[ORM\Column(type: 'string', length: 30)]
    private string $type = 'manual';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(name: 'tracking_id', type: 'string', nullable: true)]
    private ?string $trackingId = null;

    #[ORM\Column(name: 'script_url', type: 'string', length: 1000, nullable: true)]
    private ?string $scriptUrl = null;

    #[ORM\Column(name: 'inline_script', type: 'text', nullable: true)]
    private ?string $inlineScript = null;

    /** @var string[] */
    #[ORM\Column(name: 'consent_categories', type: 'json')]
    private array $consentCategories = [];

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    private array $cookies = [];

    #[ORM\Column(name: 'need_consent', type: 'boolean')]
    private bool $needConsent = true;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    /** @var Collection<int, IntegrationTranslation> */
    #[ORM\OneToMany(targetEntity: IntegrationTranslation::class, mappedBy: 'integration', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct()
    {
        $this->created = new \DateTimeImmutable();
        $this->changed = new \DateTimeImmutable();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceKey(): string
    {
        return $this->serviceKey;
    }

    public function setServiceKey(string $serviceKey): void
    {
        $this->serviceKey = $serviceKey;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): void
    {
        $this->provider = $provider;
    }

    public function getTrackingId(): ?string
    {
        return $this->trackingId;
    }

    public function setTrackingId(?string $trackingId): void
    {
        $this->trackingId = $trackingId;
    }

    public function getScriptUrl(): ?string
    {
        return $this->scriptUrl;
    }

    public function setScriptUrl(?string $scriptUrl): void
    {
        $this->scriptUrl = $scriptUrl;
    }

    public function getInlineScript(): ?string
    {
        return $this->inlineScript;
    }

    public function setInlineScript(?string $inlineScript): void
    {
        $this->inlineScript = $inlineScript;
    }

    /**
     * @return string[]
     */
    public function getConsentCategories(): array
    {
        return $this->consentCategories;
    }

    /**
     * @param string[] $consentCategories
     */
    public function setConsentCategories(array $consentCategories): void
    {
        $this->consentCategories = \array_values($consentCategories);
    }

    /**
     * @return string[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @param string[] $cookies
     */
    public function setCookies(array $cookies): void
    {
        $this->cookies = \array_values($cookies);
    }

    public function isNeedConsent(): bool
    {
        return $this->needConsent;
    }

    public function setNeedConsent(bool $needConsent): void
    {
        $this->needConsent = $needConsent;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getTranslation(string $locale): ?IntegrationTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    public function getOrCreateTranslation(string $locale): IntegrationTranslation
    {
        $translation = $this->getTranslation($locale);
        if (null === $translation) {
            $translation = new IntegrationTranslation($this, $locale);
            $this->translations->add($translation);
        }

        return $translation;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontendArray(string $locale): array
    {
        $translation = $this->getTranslation($locale) ?? ($this->translations->first() ?: null);

        return [
            'key' => $this->serviceKey,
            'type' => $this->type,
            'provider' => $this->provider,
            'trackingId' => $this->trackingId,
            'scriptUrl' => $this->scriptUrl,
            'inlineScript' => $this->inlineScript,
            'consentCategories' => $this->consentCategories,
            'cookies' => $this->cookies,
            'needConsent' => $this->needConsent,
            'title' => $translation ? $translation->getTitle() : $this->serviceKey,
            'description' => $translation ? $translation->getDescription() : null,
        ];
    }
}
