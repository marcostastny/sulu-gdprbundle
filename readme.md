# Sulu GDPR bundle

![GitHub release (with filter)](https://img.shields.io/github/v/release/Pixel-Open/sulu-gdprbundle)
[![Dependency](https://img.shields.io/badge/sulu-2.5-cca000.svg)](https://sulu.io/)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=Pixel-Open_sulu-gdprbundle&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=Pixel-Open_sulu-gdprbundle)

## Presentation

A Sulu bundle to easily manage the GDPR.
It also allows you to manage the consent banner by using the [Tarteaucitron](https://github.com/AmauriC/tarteaucitron.js/) consent management system.

![](img/backend.png)

![](img/frontend.png)

## Requirements

* PHP >= 8.0
* Sulu >= 2.5.*
* Symfony >= 5.4
* Composer

## Installation

### Install the bundle

Execute the following [composer](https://getcomposer.org/) command to add the bundle to the dependencies of your
project:

```bash
composer require pixelopen/sulu-gdprbundle
```

### Enable the bundle

Enable the bundle by adding it to the list of registered bundles in the `config/bundles.php` file of your project:

 ```php
 return [
     /* ... */
     Pixel\GDPRBundle\GDPRBundle::class => ['all' => true],
 ];
 ```

### Update schema (for dev environnement)
```shell script
bin/console do:sch:up --force
```

## Bundle Config

Import the bundle's Admin API routes in `routes_admin.yaml`
```yaml
gdpr_admin_api:
  resource: '@GDPRBundle/Resources/config/routing_admin.yaml'
  prefix: /admin/api
```

## Upgrading from v1 (single tracker → integrations)

v2 replaces the fixed provider fields (single Google Analytics code, etc.) with the
**Integrations** list. If you have v1 data, migrate it **before** the schema drops the old columns:

```shell script
# 1. create the new tables WITHOUT dropping the legacy columns yet
bin/console doctrine:schema:update --dump-sql      # review
#    run only the "CREATE TABLE gdpr_integration ..." statements, or use a migration

# 2. copy the legacy tracking codes into integrations
bin/console gdpr:integrations:migrate-settings     # --locales=de,en

# 3. now let the schema drop the legacy columns and add foreign keys
bin/console doctrine:schema:update --force
```

On a fresh install (no v1 data) just run `bin/console doctrine:schema:update --force`.

Because the Integrations list adds create/delete operations to the existing
`gdpr_settings.settings` security context, grant **Add** and **Delete** for that context to your
role under *Settings → Roles* after upgrading (an existing context does not auto‑grant newly added
permission types).

## Use
The bundle is only composed of the settings, which make the management of the GDPR very easy.

To use the GDPR management of the bundle, just check the "Use cookies management?". All the other options should be display.

The **Parameters** section will help you manage the Tarteaucitron banner, which displays the consent banner.
There are plenty of parameters, so don't hesitate to visit the repository of Tarteaucitron.

The **Integrations** tab is where you add the individual scripts/services that the banner asks
consent for (see below).

## Integrations

Each tracker, script or embed you want to gate behind consent is configured as an **integration**
on the **Integrations** tab of the GDPR settings. The list is localized — use the language switcher
to edit the texts shown in the banner per language.

![](img/integrations-list.png)

Click **Add** (or a row) to open the full‑page form. The **Type** field decides which other fields
are shown:

![](img/integration-form.png)

| Type | What it does | Fields |
|------|--------------|--------|
| **Preconfigured provider** | Wires a known provider into Tarteaucitron for you. | *Provider* (Google Analytics, Google Tag Manager, Google Ads, Bing Ads, Facebook Pixel) + *Tracking ID* |
| **Custom inline script** | Runs the pasted JavaScript when the integration is accepted. | *Inline script* |
| **Custom external JS** | Injects `<script src="…">` when the integration is accepted. | *Script URL* |
| **Manual (event only)** | Stores no code — only emits the accept/reject event so you can run your own code (e.g. a map you built yourself). | – |

Common fields for every type:

* **Key** — a unique slug (e.g. `googlemaps`). It is the Tarteaucitron service key **and** the
  suffix of the JavaScript events (`gdpr:accept:<key>`).
* **Consent Mode v2 categories** — the Google Consent Mode v2 signals that are set to `granted`
  when the integration is accepted (`analytics_storage`, `ad_storage`, …). Preconfigured providers
  get sensible defaults.
* **Enabled** — only enabled integrations are rendered on the frontend.
* **Title / Description** — the localized texts shown in the consent banner.

### Reacting to consent on the frontend

The bundle exposes a small JavaScript API so your own code can react when an integration is
accepted or rejected. This is the recommended way to gate a **Manual** integration:

```js
// run your code once the visitor accepts the "googlemaps" integration
window.gdpr.onAccept('googlemaps', function () {
    initMyMap();
});

window.gdpr.onReject('googlemaps', function () {
    // optional clean-up
});
```

`onAccept` callbacks registered *after* the visitor already accepted are fired immediately. The same
signals are also dispatched as DOM events on `document`, if you prefer listening to those:

```js
document.addEventListener('gdpr:accept:googlemaps', function (e) { /* e.detail.key === 'googlemaps' */ });
document.addEventListener('gdpr:reject:googlemaps', function () { /* … */ });
```

### Twig extension
The bundle comes with two twig functions:

**gdpr_settings()**: returns the banner settings of the bundle. No parameters are required.

Example of use:
```twig
{% set gdprSettings = gdpr_settings() %}
{{ gdprSettings.useCookieHandling }}
```

**gdpr_script()**: renders the consent banner, the enabled integrations and the consent runtime.
No parameters are required — it reads the current request locale for the banner texts. Place it in
the `<head>` of your layout.

Example of use:
```twig
{{ gdpr_script() }}
```

## Contributing
You can contribute to this bundle. The only thing you must do is respect the coding standard we implements.
You can find them in the `ecs.php` file.
