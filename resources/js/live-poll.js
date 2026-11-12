/**
 * Live page regions: simple polling so users see other people's changes without refreshing.
 *
 * Elements marked data-live="name" are Blade @fragment regions. Every few seconds this script
 * GETs the same page URL with an X-Live-Regions header; the server runs the page's normal route,
 * permission checks and queries, and returns only those regions as JSON
 * (App\Http\Middleware\RespondWithLiveRegions). Polls only read; they never change records.
 *
 * Only regions whose HTML changed are swapped. A region is left alone (and retried on the next
 * poll) while the user is working in it: focus inside it, typed or chosen input, a validation
 * error on show, or an open dialog. In that case its smaller nested regions are updated instead.
 */

const INTERVAL_MS = 3000;
const MAX_BACKOFF_MS = 30000;

/**
 * True when replacing this element would lose something the user is doing.
 *
 * @param {Element} element
 */
function isBusy(element) {
    // Focus in a field means the user is typing or choosing; focus on a button or link does not.
    const active = document.activeElement;

    if (active && element.contains(active) && active.matches('input, textarea, select, [contenteditable]')) {
        return true;
    }

    if (element.querySelector('.is-invalid, .modal.show')) {
        return true;
    }

    return [...element.querySelectorAll('input, textarea, select')].some((field) => {
        if (field.type === 'hidden' || field.type === 'submit' || field.type === 'button') {
            return false;
        }

        if (field.type === 'file') {
            return field.files.length > 0;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            return field.checked !== field.defaultChecked;
        }

        if (field.tagName === 'SELECT') {
            // Without a `selected` attribute the browser shows the first option, which is not a change.
            const options = [...field.options];
            const defaults = options.some((option) => option.defaultSelected)
                ? options.map((option) => option.defaultSelected)
                : options.map((option, index) => index === 0 && !field.multiple);

            return options.some((option, index) => option.selected !== defaults[index]);
        }

        return field.value !== field.defaultValue;
    });
}

/**
 * @param {string} html
 * @returns {Element|null}
 */
function toElement(html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();

    return template.content.firstElementChild;
}

export function initLivePoll() {
    if (!document.querySelector('[data-live]')) {
        return;
    }

    // Some pages pin the poll to a specific record (e.g. the open ticket); always stay same-origin,
    // so it works through localhost and the computer's LAN IP alike.
    const pinned = document.querySelector('[data-live-url]')?.dataset.liveUrl;
    const target = new URL(pinned || window.location.href, window.location.href);
    const pollUrl = target.pathname + target.search;

    /** @type {Map<string, string>} last HTML received per region */
    const lastHtml = new Map();
    let timer = null;
    let controller = null;
    let failures = 0;
    let stopped = false;

    function regionNames() {
        return [...new Set([...document.querySelectorAll('[data-live]')].map((element) => element.dataset.live))];
    }

    /**
     * Swap a region if it changed; if the user is busy in it, try its nested regions instead.
     *
     * @param {Element} element
     * @param {Record<string, string>} regions
     */
    function apply(element, regions) {
        const name = element.dataset.live;
        const html = regions[name];
        const nested = () => element.querySelectorAll(':scope [data-live]').forEach((child) => {
            // Only direct live children; deeper ones are handled by their own parent.
            if (child.parentElement.closest('[data-live]') === element) {
                apply(child, regions);
            }
        });

        if (html === undefined) {
            nested();

            return;
        }

        if (lastHtml.get(name) === html) {
            return;
        }

        const replacement = toElement(html);

        // First time this region is seen: compare with what the page already shows.
        if (!lastHtml.has(name) && replacement?.outerHTML === element.outerHTML) {
            lastHtml.set(name, html);

            return;
        }

        if (isBusy(element)) {
            nested();

            return;
        }

        if (replacement) {
            element.replaceWith(replacement);
            lastHtml.set(name, html);
            replacement.querySelectorAll('[data-live]').forEach((child) => {
                if (regions[child.dataset.live] !== undefined) {
                    lastHtml.set(child.dataset.live, regions[child.dataset.live]);
                }
            });
        }
    }

    /**
     * @param {Record<string, string>} regions
     */
    function applyAll(regions) {
        const openModal = document.querySelector('.modal.show');

        document.querySelectorAll('[data-live]').forEach((element) => {
            if (!element.isConnected || element.parentElement.closest('[data-live]')) {
                return; // nested regions are handled through their parent
            }

            // While a dialog is open, only regions inside that dialog update, so what the user
            // is confirming never changes underneath them. Everything else updates once it closes.
            if (openModal && !element.contains(openModal) && !openModal.contains(element)) {
                return;
            }

            apply(element, regions);
        });
    }

    function schedule(delay) {
        clearTimeout(timer);

        if (!stopped && document.visibilityState === 'visible') {
            timer = setTimeout(poll, delay);
        }
    }

    function stop(message) {
        stopped = true;
        clearTimeout(timer);
        controller?.abort();

        if (message && !document.querySelector('[data-live-stopped]')) {
            const notice = document.createElement('div');
            notice.className = 'alert alert-warning d-flex flex-wrap align-items-center gap-2';
            notice.setAttribute('role', 'status');
            notice.dataset.liveStopped = '';
            notice.innerHTML = '<span class="flex-grow-1"><i class="bi bi-pause-circle me-1" aria-hidden="true"></i><span></span></span>'
                + '<a class="alert-link" href="">Refresh the page</a>';
            notice.querySelector('span > span').textContent = message;
            document.querySelector('.app-main')?.prepend(notice);
        }
    }

    async function poll() {
        if (stopped || document.visibilityState !== 'visible') {
            return;
        }

        controller = new AbortController();

        try {
            const response = await fetch(pollUrl, {
                headers: {
                    'X-Live-Regions': regionNames().join(','),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                cache: 'no-store',
                redirect: 'manual',
                signal: controller.signal,
            });

            if (response.status === 401 || response.status === 419 || response.type === 'opaqueredirect') {
                stop('Your session has ended, so live updates stopped.');

                return;
            }

            if (response.status === 403 || response.status === 404) {
                stop('This page is no longer available to you, so live updates stopped.');

                return;
            }

            if (!response.ok) {
                throw new Error(`Poll failed with ${response.status}`);
            }

            const data = await response.json();

            if (data.stop) {
                stop('Live updates stopped.');

                return;
            }

            failures = 0;
            applyAll(data.regions ?? {});
            schedule(INTERVAL_MS);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            // Server busy or the connection dropped (e.g. the phone left Wi-Fi): wait longer each time.
            failures += 1;
            schedule(Math.min(INTERVAL_MS * 2 ** failures, MAX_BACKOFF_MS));
        } finally {
            controller = null;
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            schedule(0); // catch up straight away
        } else {
            clearTimeout(timer);
            controller?.abort();
        }
    });

    // Submitting a form: pause, so a poll never races the form's own request. If another script
    // cancels the submit (e.g. a button kept disabled), the page stays and polling resumes.
    document.addEventListener('submit', (event) => {
        clearTimeout(timer);
        controller?.abort();
        setTimeout(() => {
            if (event.defaultPrevented) {
                schedule(INTERVAL_MS);
            } else {
                stopped = true;
            }
        });
    }, true);
    window.addEventListener('pagehide', () => stop());

    // Coming back with the browser's Back button restores the old page from memory: catch up.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted && !document.querySelector('[data-live-stopped]')) {
            stopped = false;
            failures = 0;
            schedule(0);
        }
    });

    schedule(INTERVAL_MS);
}
