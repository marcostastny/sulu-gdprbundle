<?php

declare(strict_types=1);

namespace Pixel\GDPRBundle\Admin;

use Pixel\GDPRBundle\Entity\Integration;
use Pixel\GDPRBundle\Entity\Setting;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class SettingAdmin extends Admin
{
    public const TAB_VIEW = "gdpr.settings";
    public const FORM_VIEW = "gdpr.settings.form";
    public const INTEGRATIONS_LIST_VIEW = "gdpr.settings.integrations";
    public const INTEGRATION_ADD_FORM_VIEW = "gdpr.integration.add_form";
    public const INTEGRATION_ADD_DETAILS_VIEW = "gdpr.integration.add_form.details";
    public const INTEGRATION_EDIT_FORM_VIEW = "gdpr.integration.edit_form";
    public const INTEGRATION_EDIT_DETAILS_VIEW = "gdpr.integration.edit_form.details";

    private ViewBuilderFactoryInterface $viewBuilderFactory;
    private SecurityCheckerInterface $securityChecker;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(Setting::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $navigationItem = new NavigationItem("gdpr.settings");
            $navigationItem->setPosition(3);
            $navigationItem->setView(static::TAB_VIEW);
            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($navigationItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if ($this->securityChecker->hasPermission(Setting::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::TAB_VIEW, "/gdpr-settings/:id")
                    ->setResourceKey(Setting::RESOURCE_KEY)
                    ->setAttributeDefault("id", "-")
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(static::FORM_VIEW, "/details")
                    ->setResourceKey(Setting::RESOURCE_KEY)
                    ->setFormKey(Setting::FORM_KEY)
                    ->setTabTitle("sulu_admin.details")
                    ->addToolbarActions([new ToolbarAction("sulu_admin.save")])
                    ->setParent(static::TAB_VIEW)
            );

            $locales = ['de', 'en'];

            // Integrations list as a second tab of the settings view.
            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::INTEGRATIONS_LIST_VIEW, "/integrations/:locale")
                    ->setResourceKey(Integration::RESOURCE_KEY)
                    ->setListKey(Integration::LIST_KEY)
                    ->setTabTitle("gdpr_settings.integrations")
                    ->addListAdapters(["table"])
                    ->addLocales($locales)
                    ->setDefaultLocale($locales[0])
                    ->setAddView(static::INTEGRATION_ADD_FORM_VIEW)
                    ->setEditView(static::INTEGRATION_EDIT_FORM_VIEW)
                    ->addToolbarActions([new ToolbarAction("sulu_admin.add"), new ToolbarAction("sulu_admin.delete")])
                    ->setParent(static::TAB_VIEW)
            );

            // Full-page add form (new screen, localized).
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::INTEGRATION_ADD_FORM_VIEW, "/integrations/:locale/add")
                    ->setResourceKey(Integration::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setBackView(static::INTEGRATIONS_LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(static::INTEGRATION_ADD_DETAILS_VIEW, "/details")
                    ->setResourceKey(Integration::RESOURCE_KEY)
                    ->setFormKey(Integration::FORM_KEY)
                    ->setTabTitle("sulu_admin.details")
                    ->addToolbarActions([new ToolbarAction("sulu_admin.save")])
                    ->setEditView(static::INTEGRATION_EDIT_FORM_VIEW)
                    ->setParent(static::INTEGRATION_ADD_FORM_VIEW)
            );

            // Full-page edit form (new screen, localized).
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::INTEGRATION_EDIT_FORM_VIEW, "/integrations/:locale/:id")
                    ->setResourceKey(Integration::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setBackView(static::INTEGRATIONS_LIST_VIEW)
                    ->setTitleProperty("serviceKey")
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(static::INTEGRATION_EDIT_DETAILS_VIEW, "/details")
                    ->setResourceKey(Integration::RESOURCE_KEY)
                    ->setFormKey(Integration::FORM_KEY)
                    ->setTabTitle("sulu_admin.details")
                    ->addToolbarActions([new ToolbarAction("sulu_admin.save"), new ToolbarAction("sulu_admin.delete")])
                    ->setParent(static::INTEGRATION_EDIT_FORM_VIEW)
            );
        }
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts()
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                "Setting" => [
                    Setting::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }
}
