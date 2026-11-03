/**
 * File documentation for amd/src/login_method.js.
 *
 * @package   auth_magiclink
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution is prohibited.

/**
 * Present the Moodle IDP-list fallback as a distinct passwordless login method.
 *
 * Moodle exposes alternative authentication buttons only through its identity-provider list. Magic Link keeps that
 * no-JavaScript fallback, while this module corrects the rendered semantics and separates real external providers.
 *
 * @module     auth_magiclink/login_method
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 */

const METHOD_CLASS = 'auth-magiclink-passwordless';
const INTRO_CLASS = 'auth-magiclink-login-intro';
const PROVIDER_SELECTOR = [
    '.login-identityprovider',
    'a.login-identityprovider-btn',
    'form.login-shibboleth-wayf-form',
].join(', ');

/** Return the normalized path of a link without trusting its query string. */
const linkPath = link => {
    try {
        return new URL(link.href, document.baseURI).pathname.replace(/\/{2,}/g, '/');
    } catch (error) {
        return '';
    }
};

/** Determine whether an anchor opens the local Magic Link flow. */
const isMagicLink = (link, expectedPath) => link instanceof HTMLAnchorElement
    && linkPath(link) === expectedPath;

/** Resolve a link to the complete visual provider item used by Core, Boost, or Boost Union. */
const providerItemFor = (link, region) => {
    const wrapper = link.closest('.login-identityprovider');
    return wrapper && region.contains(wrapper) ? wrapper : link;
};

/** Return each provider item once, without retaining children of an already selected wrapper. */
const providerItems = region => {
    const items = [];
    region.querySelectorAll(PROVIDER_SELECTOR).forEach(candidate => {
        let item = candidate;
        if (candidate instanceof HTMLAnchorElement) {
            item = providerItemFor(candidate, region);
        }
        if (items.some(existing => existing === item || existing.contains(item))) {
            return;
        }
        for (let index = items.length - 1; index >= 0; index--) {
            if (item.contains(items[index])) {
                items.splice(index, 1);
            }
        }
        items.push(item);
    });
    return items;
};

/** Find the direct heading owned by one login-method region. */
const directHeading = region => Array.from(region.children).find(child =>
    child.matches('.login-heading, h2')) || null;

/** Create or update the passwordless heading. */
const ensureHeading = (region, text) => {
    let heading = directHeading(region);
    if (!heading) {
        heading = document.createElement('h2');
        heading.className = 'h5 login-heading';
        region.prepend(heading);
    }
    heading.textContent = text;
    heading.classList.add('auth-magiclink-login-heading');
    return heading;
};

/** Create or update the short passwordless-method explanation. */
const ensureIntro = (region, heading, text) => {
    let intro = Array.from(region.children).find(child => child.classList.contains(INTRO_CLASS));
    if (!intro) {
        intro = document.createElement('p');
        intro.className = `${INTRO_CLASS} mb-3`;
        heading.insertAdjacentElement('afterend', intro);
    }
    intro.textContent = text;
};

/** Update Boost Union's surrounding method labels when this method contains only Magic Link. */
const updateMethodLabels = (region, heading) => {
    const method = region.closest('.theme_boost_union-loginmethod');
    if (!method) {
        return;
    }
    method.classList.add(METHOD_CLASS);
    method.dataset.loginMethod = 'passwordless';

    const accordionLabel = method.querySelector('.accordion-header .login-heading');
    if (accordionLabel) {
        accordionLabel.textContent = heading;
    }
    if (!method.id) {
        return;
    }
    const tab = document.getElementById(`${method.id}-tab`);
    if (!tab) {
        return;
    }
    const tabLabel = tab.querySelector('.login-heading, .login-method-label');
    if (tabLabel) {
        tabLabel.textContent = heading;
    } else if (tab.childElementCount === 0) {
        tab.textContent = heading;
    }
};

/** Mark every moved link for styling, diagnostics, and future theme integrations. */
const markLinks = (items, expectedPath) => {
    items.forEach(item => {
        const link = item instanceof HTMLAnchorElement ? item : item.querySelector('a[href]');
        if (isMagicLink(link, expectedPath)) {
            link.dataset.authComponent = 'auth_magiclink';
            link.dataset.loginMethod = 'passwordless';
        }
    });
};

/** Re-label an IDP region which consists exclusively of the Magic Link entry. */
const convertExclusiveRegion = (region, magicItems, configuration) => {
    region.classList.add(METHOD_CLASS);
    region.dataset.loginMethod = 'passwordless';
    const heading = ensureHeading(region, configuration.heading);
    ensureIntro(region, heading, configuration.description);
    markLinks(magicItems, configuration.path);
    updateMethodLabels(region, configuration.heading);
};

/** Move Magic Link into its own region while leaving external providers under the original IDP heading. */
const splitMixedRegion = (region, magicItems, configuration) => {
    const passwordless = document.createElement('div');
    passwordless.className = `${METHOD_CLASS} mb-3`;
    passwordless.dataset.loginMethod = 'passwordless';
    const heading = ensureHeading(passwordless, configuration.heading);
    ensureIntro(passwordless, heading, configuration.description);
    magicItems.forEach(item => passwordless.append(item));
    markLinks(magicItems, configuration.path);
    region.insertAdjacentElement('beforebegin', passwordless);
};

/** Apply the semantic separation once to every rendered provider region. */
const enhance = configuration => {
    document.querySelectorAll('.login-identityproviders').forEach(region => {
        if (region.dataset.authMagiclinkProcessed === '1') {
            return;
        }
        const items = providerItems(region);
        const magicItems = items.filter(item => {
            const link = item instanceof HTMLAnchorElement ? item : item.querySelector('a[href]');
            return isMagicLink(link, configuration.path);
        });
        if (!magicItems.length) {
            return;
        }
        region.dataset.authMagiclinkProcessed = '1';
        if (magicItems.length === items.length) {
            convertExclusiveRegion(region, magicItems, configuration);
        } else {
            splitMixedRegion(region, magicItems, configuration);
        }
        document.body.classList.add('auth-magiclink-login-method');
    });
};

/** Initialize the login-page enhancement after the form has entered the DOM. */
export const init = configuration => {
    const normalized = {
        path: String(configuration?.path || '/auth/magiclink/login.php').replace(/\/{2,}/g, '/'),
        heading: String(configuration?.heading || ''),
        description: String(configuration?.description || ''),
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => enhance(normalized), {once: true});
    } else {
        enhance(normalized);
    }
};
