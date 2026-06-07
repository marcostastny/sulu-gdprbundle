<?php

namespace Pixel\GDPRBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gdpr_integration_translation')]
#[ORM\UniqueConstraint(name: 'gdpr_integration_locale', columns: ['integration_id', 'locale'])]
class IntegrationTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Integration::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'integration_id', nullable: false, onDelete: 'CASCADE')]
    private ?Integration $integration = null;

    #[ORM\Column(type: 'string', length: 10)]
    private string $locale;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct(Integration $integration, string $locale)
    {
        $this->integration = $integration;
        $this->locale = $locale;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntegration(): ?Integration
    {
        return $this->integration;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
