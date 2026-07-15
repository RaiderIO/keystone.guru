/**
 * Local (non-CI) Puppeteer E2E script for the dungeon route creation flow (issue #3534).
 *
 * Drives the REAL browser code path end to end - real login form, real game version switcher,
 * real Tom Select UI (`.selectpicker` / tom-select, see resources/assets/js/selectpicker.js) - to
 * catch the kind of bug that a jsdom/vitest contract test cannot: the browser producing a
 * malformed POST body after the JS runs (see v15.4.0 / issue #3514).
 *
 * Not run in CI. Requires a running worktree/main Docker stack plus the `chrome` compose service:
 *
 *   docker compose --profile chrome up -d chrome
 *   docker compose exec -T app sh -c 'cd /var/www && node sh/e2e/create-route.e2e.js \
 *       --email user@app.com --password password'
 *
 * Options:
 *   --email <email>       Required. An existing user with role user|admin.
 *   --password <password> Required.
 *   --base-url <url>      Default: http://nginx (the site as seen from inside the app container).
 *   --keep                Skip deleting the routes created by this script.
 *
 * Uses the same CDP-connect pattern as .claude/skills/headless-browser-verify/browse.js: resolve
 * the `chrome` compose service's IP, connect via puppeteer.connect(), and disconnect (not close)
 * when done so the browser stays warm.
 *
 * Exits non-zero if any assertion fails. Prints a structured JSON summary to stdout.
 */
const puppeteer = require('puppeteer');
const http = require('http');
const dns = require('dns').promises;

function arg(name, fallback = null) {
    const i = process.argv.indexOf('--' + name);
    return i === -1 ? fallback : process.argv[i + 1];
}

function flag(name) {
    return process.argv.includes('--' + name);
}

/**
 * Connect to the compose chrome service. Chrome's DevTools endpoint rejects Host headers that are
 * not an IP or localhost, so resolve the service name to an IP first and connect through that.
 * Mirrors browse.js, but does NOT fall back to a local launch: if the service is unreachable we
 * want a clear, actionable failure rather than a silently different browser.
 */
async function connectToChromeService(host, port) {
    let address;
    try {
        ({address} = await dns.lookup(host));
    } catch (e) {
        throw new Error(
            `Could not resolve chrome service host "${host}": ${e.message}\n` +
            'Start it with: docker compose --profile chrome up -d chrome',
        );
    }

    const version = await new Promise((resolve, reject) => {
        const req = http.get({host: address, port, path: '/json/version', timeout: 3000}, res => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => {
                try {
                    resolve(JSON.parse(body));
                } catch (e) {
                    reject(new Error(`Chrome /json/version returned non-JSON: ${e.message}`));
                }
            });
        });
        req.on('error', reject);
        req.on('timeout', () => req.destroy(new Error('timeout')));
    }).catch(e => {
        throw new Error(
            `Could not reach the chrome CDP endpoint at ${host}:${port} (${address}): ${e.message}\n` +
            'Start it with: docker compose --profile chrome up -d chrome',
        );
    });

    const wsEndpoint = version.webSocketDebuggerUrl.replace(/ws:\/\/[^/]+/, `ws://${address}:${port}`);

    return puppeteer.connect({browserWSEndpoint: wsEndpoint, defaultViewport: null});
}

/**
 * The cookieconsent2 banner (`.cc-window`, fixed-bottom) intercepts clicks aimed at whatever sits
 * underneath it (e.g. the login submit button). It is not tied to the user account, so it
 * reappears on every fresh page load. Just rip it out after every navigation.
 */
async function dismissCookieBanner(page) {
    await page.evaluate(() => {
        document.querySelectorAll('.cc-window').forEach(el => el.remove());
    });
}

async function goto(page, url) {
    await page.goto(url, {waitUntil: 'load', timeout: 30000});
    await dismissCookieBanner(page);
}

/**
 * Real DOM `.click()` via page.evaluate(), rather than Puppeteer's synthetic mouse click.
 *
 * Tom Select's open-on-click was flaky under Puppeteer's coordinate-based synthetic click in this
 * environment (no visible cause found - same selector, same wait, intermittently never opened the
 * dropdown). Dispatching a real `.click()` on the element from inside the page was 100% reliable
 * across dozens of manual runs and still exercises the exact same click handlers Tom Select
 * registers - it just isn't driven by synthesized mouse coordinates.
 */
async function nativeClick(page, selector) {
    const ok = await page.evaluate(sel => {
        const el = document.querySelector(sel);
        if (!el) {
            return false;
        }
        el.click();
        return true;
    }, selector);
    if (!ok) {
        throw new Error(`nativeClick: no element found for selector "${selector}"`);
    }
}

function tomSelectWrapper(id) {
    return `#${id} ~ .ts-wrapper`;
}

/** Opens a Tom Select dropdown and waits for it to actually become visible. */
async function openTomSelect(page, id) {
    await page.waitForSelector(tomSelectWrapper(id), {timeout: 10000});
    await nativeClick(page, `${tomSelectWrapper(id)} .ts-control`);
    await page.waitForFunction((wrapperSelector) => {
        const dd = document.querySelector(`${wrapperSelector} .ts-dropdown`);
        return !!dd && getComputedStyle(dd).display !== 'none';
    }, {timeout: 5000}, tomSelectWrapper(id));
}

/** Types into a Tom Select `dropdown_input` live-search box (must already be open). */
async function typeIntoLiveSearch(page, id, text) {
    await page.type(`${tomSelectWrapper(id)} .dropdown-input`, text, {delay: 20});
    // Give Tom Select's refreshOptions a moment to re-filter.
    await new Promise(r => setTimeout(r, 400));
}

async function tomSelectOptions(page, id) {
    return page.evaluate((wrapperSelector) => {
        return Array.from(document.querySelectorAll(`${wrapperSelector} .ts-dropdown [data-selectable]`))
            .map(o => ({text: o.textContent.trim(), value: o.dataset.value}));
    }, tomSelectWrapper(id));
}

async function selectTomSelectOptionByValue(page, id, value) {
    await nativeClick(page, `${tomSelectWrapper(id)} .ts-dropdown [data-value="${value}"]`);
}

async function selectFirstTomSelectOption(page, id) {
    await nativeClick(page, `${tomSelectWrapper(id)} .ts-dropdown [data-selectable]`);
}

async function tomSelectValue(page, id) {
    return page.evaluate((selId) => document.querySelector(`#${selId}`)?.value ?? null, id);
}

/** The create-route form has no id/class of its own; anchor off the dungeon select it contains. */
async function getCreateRouteForm(page) {
    return page.evaluateHandle(() => document.querySelector('#dungeon_id_select')?.closest('form') ?? null);
}

async function submitCreateRouteForm(page) {
    const formHandle = await getCreateRouteForm(page);
    const submitHandle = await formHandle.asElement()?.$('input[type="submit"], button[type="submit"]');
    if (!submitHandle) {
        throw new Error('Could not find the submit button inside the create-route form');
    }
    await Promise.all([
        page.waitForNavigation({waitUntil: 'load', timeout: 20000}),
        submitHandle.evaluate(el => el.click()),
    ]);
    await dismissCookieBanner(page);
}

/** {name, href, active} for every game version in the header switcher (`.game_version_header`). */
async function scrapeGameVersions(page) {
    return page.evaluate(() => {
        return Array.from(document.querySelectorAll('.game_version_header .game_version a')).map(a => ({
            href: a.getAttribute('href'),
            text: a.textContent.trim().replace(/\s+/g, ' '),
            active: a.classList.contains('active'),
        }));
    });
}

/** Switches game version via a plain GET to the scraped switcher href (no Tom Select involved). */
async function switchGameVersion(page, baseUrl, href) {
    const url = href.startsWith('http') ? href : `${baseUrl}${href}`;
    await goto(page, url);
}

/**
 * Deletes a dungeon route the same way the profile table's delete button does
 * (resources/assets/js/custom/inline/dungeonroute/table.js `_promptDeleteDungeonRouteClicked`):
 * `DELETE /ajax/{public_key}`, with the CSRF token off the page's own meta tag.
 */
async function deleteRoute(page, baseUrl, publicKey) {
    return page.evaluate(async (base, key) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch(`${base}/ajax/${key}`, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': token ?? '', 'Accept': 'application/json'},
            credentials: 'same-origin',
        });
        return {status: res.status, ok: res.ok};
    }, baseUrl, publicKey);
}

/** Path is `/route/{dungeonSlug}/{publicKey}/{titleSlug}/edit[/{floorIndex}]`. */
function parseRouteUrl(url) {
    const m = url.match(/\/route\/([^/]+)\/([^/]+)\/([^/]+)\/edit/);
    if (!m) {
        return null;
    }
    return {dungeonSlug: m[1], publicKey: m[2], titleSlug: m[3]};
}

async function main() {
    const email = arg('email');
    const password = arg('password');
    const baseUrl = (arg('base-url', 'http://nginx')).replace(/\/$/, '');
    const keep = flag('keep');

    if (!email || !password) {
        console.error('usage: node create-route.e2e.js --email <e> --password <p> [--base-url http://nginx] [--keep]');
        process.exit(2);
    }

    const steps = [];
    const pageErrors = [];
    const consoleErrors = [];
    const result = {
        baseUrl,
        email,
        keep,
        steps,
        retail: null,
        classic: null,
        staleDifficultyProbe: null,
        originalGameVersion: null,
        restoredGameVersion: null,
        pageErrors,
        consoleErrors,
    };

    /**
     * Runs a named step, records pass/fail, and re-throws so the caller can decide whether
     * downstream steps still make sense to attempt.
     */
    async function step(name, fn) {
        try {
            const detail = await fn();
            steps.push({name, pass: true, detail: detail === undefined ? undefined : detail});
            return detail;
        } catch (e) {
            steps.push({name, pass: false, error: e.message});
            throw e;
        }
    }

    function assert(condition, message) {
        if (!condition) {
            throw new Error(`assertion failed: ${message}`);
        }
    }

    let browser;
    try {
        browser = await connectToChromeService(process.env.CHROME_HOST || 'chrome', parseInt(process.env.CHROME_PORT || '9222', 10));
    } catch (e) {
        console.error(e.message);
        console.log(JSON.stringify({...result, fatal: e.message}, null, 2));
        process.exit(1);
        return;
    }

    const page = await browser.newPage();
    await page.setViewport({width: 1600, height: 1200});

    // The chrome service's browser stays warm between runs (see SKILL.md), so a stale session
    // cookie from a previous invocation (possibly a different user) could still be active. Start
    // from a clean slate so the --email/--password given here are the ones actually driving.
    const cdpSession = await page.createCDPSession();
    await cdpSession.send('Network.clearBrowserCookies');

    page.on('pageerror', e => pageErrors.push(e.message));
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text().substring(0, 300));
        }
    });

    let originalGameVersionHref = null;

    try {
        await step('login', async () => {
            await goto(page, `${baseUrl}/login`);

            const formHandle = await page.evaluateHandle(() => {
                return Array.from(document.querySelectorAll('form')).find(f => {
                    return f.querySelector('input[name="email"]')
                        && !!(f.offsetWidth || f.offsetHeight || f.getClientRects().length);
                });
            });
            const el = formHandle.asElement();
            assert(el !== null, 'could not find a visible login form on /login');

            const emailHandle = await el.$('input[name="email"]');
            const passwordHandle = await el.$('input[name="password"]');
            const submitHandle = await el.$('button[type="submit"], input[type="submit"]');
            await emailHandle.type(email);
            await passwordHandle.type(password);
            await Promise.all([
                page.waitForNavigation({waitUntil: 'load', timeout: 15000}),
                submitHandle.evaluate(btn => btn.click()),
            ]);
            await dismissCookieBanner(page);

            const loggedIn = await page.evaluate(() => document.body.innerText.toLowerCase().includes('logout')
                || document.querySelectorAll('form[action*="/logout"]').length > 0);
            assert(loggedIn, 'no logout affordance found after submitting the login form - login likely failed');
        });

        await step('scrape game version switcher', async () => {
            const gameVersions = await scrapeGameVersions(page);
            assert(gameVersions.length > 0, 'no .game_version_header .game_version a links found');
            const active = gameVersions.find(gv => gv.active);
            assert(active !== undefined, 'no active game version found in the switcher');
            originalGameVersionHref = active.href;
            result.originalGameVersion = active.text;
            return {gameVersions};
        });

        // ---------------------------------------------------------------------------------
        // Retail flow
        // ---------------------------------------------------------------------------------
        const retail = {dungeonSelected: null, routeUrl: null, routeKey: null, cleanedUp: null};
        result.retail = retail;

        await step('retail: ensure retail game version', async () => {
            const gameVersions = await scrapeGameVersions(page);
            const retailGv = gameVersions.find(gv => gv.text.toUpperCase().includes('RETAIL'));
            assert(retailGv !== undefined, 'no RETAIL entry in the game version switcher');
            if (!retailGv.active) {
                await switchGameVersion(page, baseUrl, retailGv.href);
            }
        });

        await step('retail: open /new and select a dungeon via Tom Select', async () => {
            await goto(page, `${baseUrl}/new`);
            await openTomSelect(page, 'dungeon_id_select');
            const options = await tomSelectOptions(page, 'dungeon_id_select');
            assert(options.length > 0, 'dungeon Tom Select has no selectable options');
            await selectFirstTomSelectOption(page, 'dungeon_id_select');
            const value = await tomSelectValue(page, 'dungeon_id_select');
            assert(value !== null && value !== '', 'no dungeon_id value after selecting a dungeon');
            retail.dungeonSelected = {value, text: options[0].text};
        });

        await step('retail: fill title and submit', async () => {
            const title = `E2E Retail Route ${Date.now()}`;
            await page.type('#dungeon_route_title', title);
            await submitCreateRouteForm(page);

            const parsed = parseRouteUrl(page.url());
            assert(parsed !== null, `expected navigation to /route/{dungeon}/{key}/{title}/edit, got ${page.url()}`);
            retail.routeUrl = page.url();
            retail.routeKey = parsed.publicKey;
        });

        if (retail.routeKey && !keep) {
            await step('retail: cleanup (delete created route)', async () => {
                const res = await deleteRoute(page, baseUrl, retail.routeKey);
                assert(res.status === 204, `expected 204 deleting route ${retail.routeKey}, got ${res.status}`);
                retail.cleanedUp = true;
            });
        } else {
            retail.cleanedUp = false;
        }

        // ---------------------------------------------------------------------------------
        // Classic flow: Serpentshrine Cavern speedrun, 25-man
        // ---------------------------------------------------------------------------------
        const classic = {dungeonSelected: null, difficultySelected: null, routeUrl: null, routeKey: null, cleanedUp: null};
        result.classic = classic;

        await step('classic: switch to classic game version', async () => {
            const gameVersions = await scrapeGameVersions(page);
            const classicGv = gameVersions.find(gv => gv.text.toUpperCase().includes('CLASSIC'));
            assert(classicGv !== undefined, 'no CLASSIC entry in the game version switcher');
            if (!classicGv.active) {
                await switchGameVersion(page, baseUrl, classicGv.href);
            }
        });

        await step('classic: open /new and live-search Serpentshrine Cavern', async () => {
            await goto(page, `${baseUrl}/new`);
            await openTomSelect(page, 'dungeon_id_select');
            await typeIntoLiveSearch(page, 'dungeon_id_select', 'Serpentshrine');
            const options = await tomSelectOptions(page, 'dungeon_id_select');
            assert(
                options.length === 1 && options[0].text === 'Serpentshrine Cavern',
                `expected live-search to narrow to exactly "Serpentshrine Cavern", got ${JSON.stringify(options)}`,
            );
            await selectFirstTomSelectOption(page, 'dungeon_id_select');
            classic.dungeonSelected = options[0];
        });

        await step('classic: difficulty container becomes visible with a 25-man option', async () => {
            await page.waitForFunction(() => {
                const el = document.querySelector('#dungeon_difficulty_select_container');
                return !!el && getComputedStyle(el).display !== 'none';
            }, {timeout: 5000});

            await openTomSelect(page, 'dungeon_difficulty_select');
            const options = await tomSelectOptions(page, 'dungeon_difficulty_select');
            // DungeonConstants::DIFFICULTY_25_MAN => 2. Only assert 25-man is present: this dev
            // DB's seeded SSC only has the 25-man speedrun difficulty enabled, not 10-man.
            const has25man = options.some(o => o.value === '2');
            assert(has25man, `expected a 25-man (value=2) option, got ${JSON.stringify(options)}`);
            await selectTomSelectOptionByValue(page, 'dungeon_difficulty_select', '2');
            const value = await tomSelectValue(page, 'dungeon_difficulty_select');
            assert(value === '2', `expected dungeon_difficulty=2 after selecting 25-man, got ${value}`);
            classic.difficultySelected = {value, options};
        });

        await step('classic: fill title and submit', async () => {
            const title = `E2E Classic SSC 25man ${Date.now()}`;
            await page.type('#dungeon_route_title', title);
            await submitCreateRouteForm(page);

            const parsed = parseRouteUrl(page.url());
            assert(parsed !== null, `expected navigation to /route/{dungeon}/{key}/{title}/edit, got ${page.url()}`);
            classic.routeUrl = page.url();
            classic.routeKey = parsed.publicKey;
        });

        if (classic.routeKey && !keep) {
            await step('classic: cleanup (delete created route)', async () => {
                const res = await deleteRoute(page, baseUrl, classic.routeKey);
                assert(res.status === 204, `expected 204 deleting route ${classic.routeKey}, got ${res.status}`);
                classic.cleanedUp = true;
            });
        } else {
            classic.cleanedUp = false;
        }

        // ---------------------------------------------------------------------------------
        // Bonus probe (non-fatal, informational): stale dungeon_difficulty after switching a
        // speedrun dungeon -> a non-speedrun one. See issue #3535.
        // dungeondifficultyselect.js only *hides* the container on switch-away; it never clears
        // the select's options/value, so the hidden <select> still submits the stale difficulty.
        // ---------------------------------------------------------------------------------
        try {
            await goto(page, `${baseUrl}/new`);
            await openTomSelect(page, 'dungeon_id_select');
            await typeIntoLiveSearch(page, 'dungeon_id_select', 'Serpentshrine');
            await selectFirstTomSelectOption(page, 'dungeon_id_select');
            await page.waitForFunction(() => {
                const el = document.querySelector('#dungeon_difficulty_select_container');
                return !!el && getComputedStyle(el).display !== 'none';
            }, {timeout: 5000});
            await openTomSelect(page, 'dungeon_difficulty_select');
            await selectTomSelectOptionByValue(page, 'dungeon_difficulty_select', '2');

            // Switch to a non-speedrun classic dungeon (Blackfathom Deeps).
            await openTomSelect(page, 'dungeon_id_select');
            await typeIntoLiveSearch(page, 'dungeon_id_select', 'Blackfathom');
            const blackfathomOptions = await tomSelectOptions(page, 'dungeon_id_select');
            await selectFirstTomSelectOption(page, 'dungeon_id_select');

            const containerDisplay = await page.evaluate(() => {
                const el = document.querySelector('#dungeon_difficulty_select_container');
                return el ? getComputedStyle(el).display : null;
            });
            const formEntries = await page.evaluate(() => {
                const form = document.querySelector('#dungeon_id_select')?.closest('form');
                return form ? Array.from(new FormData(form).entries()) : null;
            });

            result.staleDifficultyProbe = {
                switchedTo: blackfathomOptions[0] ?? null,
                difficultyContainerDisplay: containerDisplay,
                wouldSubmitDungeonDifficulty: formEntries?.find(([k]) => k === 'dungeon_difficulty')?.[1] ?? null,
                formEntries,
                note: 'informational only, see issue #3535 - the hidden select still submits its stale value',
            };
        } catch (e) {
            result.staleDifficultyProbe = {error: e.message};
        }
    } catch (e) {
        // A step() failure already recorded itself in `steps` and rethrew to abort the remaining
        // flow (e.g. no point attempting the classic flow if login failed). Swallow it here so the
        // JSON summary below still gets printed, with `finally` still restoring the game version.
        result.fatalError = e.message;
    } finally {
        // Always restore the user's original game version, even on failure.
        if (originalGameVersionHref) {
            try {
                await switchGameVersion(page, baseUrl, originalGameVersionHref);
                const gameVersions = await scrapeGameVersions(page);
                result.restoredGameVersion = gameVersions.find(gv => gv.active)?.text ?? null;
            } catch (e) {
                steps.push({name: 'restore original game version', pass: false, error: e.message});
            }
        }

        await page.close();
        browser.disconnect();
    }

    const failed = steps.filter(s => !s.pass);
    result.passed = failed.length === 0;
    result.failedSteps = failed.map(s => s.name);

    console.log(JSON.stringify(result, null, 2));
    process.exit(result.passed ? 0 : 1);
}

main().catch(e => {
    console.error('Unexpected error:', e);
    process.exit(1);
});
