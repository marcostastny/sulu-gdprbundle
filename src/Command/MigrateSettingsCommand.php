<?php

declare(strict_types=1);

namespace Pixel\GDPRBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\GDPRBundle\Entity\Integration;
use Pixel\GDPRBundle\Provider\IntegrationDefaults;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'gdpr:integrations:migrate-settings',
    description: 'Migrate legacy v1 provider tracking codes into integrations.',
)]
class MigrateSettingsCommand extends Command
{
    /** legacy column => provider */
    private const LEGACY_MAP = [
        'google_analytics_gtag_js' => 'gtag',
        'google_tag_manager' => 'googletagmanager',
        'google_ads' => 'googleads',
        'bing_ads' => 'bingads',
        'pixel_facebook' => 'facebookpixel',
    ];

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('locales', null, InputOption::VALUE_REQUIRED, 'Comma-separated locales for titles', 'de,en');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locales = \array_filter(\explode(',', (string) $input->getOption('locales')));

        $connection = $this->entityManager->getConnection();
        $columns = \implode(', ', \array_keys(self::LEGACY_MAP));

        try {
            $row = $connection->fetchAssociative("SELECT {$columns} FROM gdpr_settings LIMIT 1");
        } catch (\Throwable $e) {
            $io->warning('No legacy gdpr_settings columns found; nothing to migrate.');

            return Command::SUCCESS;
        }

        if (false === $row) {
            $io->note('No gdpr_settings row; nothing to migrate.');

            return Command::SUCCESS;
        }

        $repository = $this->entityManager->getRepository(Integration::class);
        $created = 0;

        foreach (self::LEGACY_MAP as $column => $provider) {
            $value = $row[$column] ?? null;
            if (null === $value || '' === \trim((string) $value)) {
                continue;
            }
            if (null !== $repository->findOneBy(['serviceKey' => $provider])) {
                $io->note(sprintf('Integration "%s" already exists, skipping.', $provider));
                continue;
            }

            $integration = new Integration();
            $integration->setServiceKey($provider);
            $integration->setType(IntegrationDefaults::TYPE_PRECONFIGURED);
            $integration->setProvider($provider);
            $integration->setTrackingId((string) $value);
            $integration->setConsentCategories(IntegrationDefaults::categoriesForProvider($provider));
            $integration->setEnabled(true);
            foreach ($locales as $locale) {
                $integration->getOrCreateTranslation($locale)->setTitle(IntegrationDefaults::displayName($provider));
            }
            $this->entityManager->persist($integration);
            ++$created;
            $io->writeln(sprintf('Created integration <info>%s</info> (%s).', $provider, $value));
        }

        $this->entityManager->flush();
        $io->success(sprintf('Migration complete: %d integration(s) created.', $created));

        return Command::SUCCESS;
    }
}
