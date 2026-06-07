<?php

declare(strict_types=1);

namespace Pixel\GDPRBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Pixel\GDPRBundle\Entity\Integration;
use Pixel\GDPRBundle\Entity\Setting;
use Pixel\GDPRBundle\Provider\IntegrationDefaults;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class IntegrationController extends AbstractRestController implements SecuredControllerInterface
{
    private const GRANT_MAP = [
        'grantAnalyticsStorage' => 'analytics_storage',
        'grantAdStorage' => 'ad_storage',
        'grantAdUserData' => 'ad_user_data',
        'grantAdPersonalization' => 'ad_personalization',
        'grantFunctionalityStorage' => 'functionality_storage',
        'grantPersonalizationStorage' => 'personalization_storage',
        'grantSecurityStorage' => 'security_storage',
    ];

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private EntityManagerInterface $entityManager,
        private RestHelperInterface $restHelper,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        ?TokenStorageInterface $tokenStorage = null,
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $locale = (string) $request->query->get('locale');
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(Integration::LIST_KEY);
        $listBuilder = $this->listBuilderFactory->create(Integration::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
        $listBuilder->setParameter('locale', $locale);

        $list = new PaginatedRepresentation(
            $listBuilder->execute(),
            Integration::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count()
        );

        return $this->handleView($this->view($list, 200));
    }

    public function getAction(int $id, Request $request): Response
    {
        $locale = (string) $request->query->get('locale');
        $integration = $this->entityManager->getRepository(Integration::class)->find($id);
        if (null === $integration) {
            return $this->handleView($this->view(null, 404));
        }

        return $this->handleView($this->view($this->toArray($integration, $locale)));
    }

    public function postAction(Request $request): Response
    {
        $locale = (string) $request->query->get('locale');
        $integration = new Integration();
        $this->mapDataToEntity($request->request->all(), $integration, $locale);
        $this->entityManager->persist($integration);
        $this->entityManager->flush();

        return $this->handleView($this->view($this->toArray($integration, $locale)));
    }

    public function putAction(int $id, Request $request): Response
    {
        $locale = (string) $request->query->get('locale');
        $integration = $this->entityManager->getRepository(Integration::class)->find($id);
        if (null === $integration) {
            return $this->handleView($this->view(null, 404));
        }
        $this->mapDataToEntity($request->request->all(), $integration, $locale);
        $this->entityManager->flush();

        return $this->handleView($this->view($this->toArray($integration, $locale)));
    }

    public function deleteAction(int $id): Response
    {
        $integration = $this->entityManager->getRepository(Integration::class)->find($id);
        if (null !== $integration) {
            $this->entityManager->remove($integration);
            $this->entityManager->flush();
        }

        return $this->handleView($this->view(null, 204));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapDataToEntity(array $data, Integration $entity, string $locale): void
    {
        $entity->setServiceKey((string) ($data['serviceKey'] ?? $entity->getServiceKey()));
        $entity->setType((string) ($data['type'] ?? IntegrationDefaults::TYPE_MANUAL));
        $entity->setProvider($data['provider'] ?? null);
        $entity->setTrackingId($data['trackingId'] ?? null);
        $entity->setScriptUrl($data['scriptUrl'] ?? null);
        $entity->setInlineScript($data['inlineScript'] ?? null);
        $entity->setEnabled((bool) ($data['enabled'] ?? true));

        $categories = [];
        foreach (self::GRANT_MAP as $field => $signal) {
            if (!empty($data[$field])) {
                $categories[] = $signal;
            }
        }
        // Seed sensible defaults for a preconfigured provider if the editor left them all off.
        if ([] === $categories
            && IntegrationDefaults::TYPE_PRECONFIGURED === $entity->getType()
            && null !== $entity->getProvider()
        ) {
            $categories = IntegrationDefaults::categoriesForProvider($entity->getProvider());
        }
        $entity->setConsentCategories($categories);

        $translation = $entity->getOrCreateTranslation($locale);
        $translation->setTitle($data['title'] ?? null);
        $translation->setDescription($data['description'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Integration $entity, string $locale): array
    {
        $translation = $entity->getTranslation($locale);
        $categories = $entity->getConsentCategories();

        $result = [
            'id' => $entity->getId(),
            'serviceKey' => $entity->getServiceKey(),
            'type' => $entity->getType(),
            'provider' => $entity->getProvider(),
            'trackingId' => $entity->getTrackingId(),
            'scriptUrl' => $entity->getScriptUrl(),
            'inlineScript' => $entity->getInlineScript(),
            'enabled' => $entity->isEnabled(),
            'title' => $translation?->getTitle(),
            'description' => $translation?->getDescription(),
        ];
        foreach (self::GRANT_MAP as $field => $signal) {
            $result[$field] = \in_array($signal, $categories, true);
        }

        return $result;
    }

    public function getSecurityContext(): string
    {
        return Setting::SECURITY_CONTEXT;
    }

    public function getLocale(Request $request): ?string
    {
        return $request->query->get('locale');
    }
}
