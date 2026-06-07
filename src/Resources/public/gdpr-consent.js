/* GDPR bundle consent runtime: builds tarteaucitron services from integration
 * config, runs custom code on accept, flips Google Consent Mode v2 signals, and
 * exposes a small subscription API. */
(function () {
    "use strict";

    var accepted = {};
    var listeners = { accept: {}, reject: {} };

    function fire(kind, key) {
        if (kind === "accept") {
            accepted[key] = true;
        }
        (listeners[kind][key] || []).forEach(function (cb) {
            try { cb(); } catch (e) { /* swallow integration callback errors */ }
        });
        document.dispatchEvent(new CustomEvent("gdpr:" + kind + ":" + key, { detail: { key: key } }));
    }

    function subscribe(kind, key, cb) {
        (listeners[kind][key] = listeners[kind][key] || []).push(cb);
        if (kind === "accept" && accepted[key]) {
            try { cb(); } catch (e) { /* noop */ }
        }
    }

    function grant(categories) {
        if (!categories || !categories.length || typeof window.tac_gtag !== "function") {
            return;
        }
        var update = {};
        categories.forEach(function (c) { update[c] = "granted"; });
        window.tac_gtag("consent", "update", update);
    }

    function runCustom(integration) {
        if (integration.type === "custom_inline" && integration.inlineScript) {
            try { (0, eval)(integration.inlineScript); } catch (e) { /* invalid editor script */ }
        } else if (integration.type === "custom_external" && integration.scriptUrl) {
            var s = document.createElement("script");
            s.src = integration.scriptUrl;
            s.async = true;
            document.head.appendChild(s);
        }
    }

    function nativeJob(integration) {
        // Preconfigured providers reuse tarteaucitron's native services.
        var t = window.tarteaucitron;
        switch (integration.provider) {
            case "gtag":
                t.user.gtagUa = integration.trackingId;
                t.user.gtagMore = function () {};
                t.job.push("gtag");
                break;
            case "googletagmanager":
                t.user.googletagmanagerId = integration.trackingId;
                t.job.push("googletagmanager");
                break;
            case "googleads":
                t.user.googleadsId = integration.trackingId;
                t.job.push("googleads");
                break;
            case "bingads":
                t.user.bingadsID = integration.trackingId;
                t.job.push("bingads");
                break;
            case "facebookpixel":
                t.user.facebookpixelId = integration.trackingId;
                t.user.facebookpixelMore = function () {};
                t.job.push("facebookpixel");
                break;
            default:
                break;
        }
    }

    function customService(integration) {
        var t = window.tarteaucitron;
        var key = integration.key;
        t.services[key] = {
            key: key,
            type: "other",
            name: integration.title || key,
            needConsent: integration.needConsent !== false,
            cookies: integration.cookies || [],
            readmoreLink: "",
            js: function () {
                grant(integration.consentCategories);
                runCustom(integration);
                fire("accept", key);
            },
            fallback: function () {
                fire("reject", key);
            }
        };
        (t.job = t.job || []).push(key);
    }

    window.gdpr = {
        onAccept: function (key, cb) { subscribe("accept", key, cb); },
        onReject: function (key, cb) { subscribe("reject", key, cb); },
        boot: function (params, integrations) {
            var t = window.tarteaucitron;
            if (!t) { return; }
            t.user = t.user || {};
            t.job = t.job || [];
            (integrations || []).forEach(function (integration) {
                if (integration.type === "preconfigured") {
                    // GCM grant happens via tarteaucitron's own gcm services for native jobs;
                    // we still re-dispatch a namespaced event so the API works.
                    nativeJob(integration);
                    var key = integration.key;
                    document.addEventListener(integration.provider + "_loaded", function () { fire("accept", key); });
                } else {
                    customService(integration);
                }
            });
            t.init(params);
        }
    };
})();
