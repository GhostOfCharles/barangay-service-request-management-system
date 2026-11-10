/**
 * Searchable resident field for the New Service Request form.
 *
 * Staff type part of a name, purok, or contact number and pick from the matches.
 * "New resident" swaps the field for inline resident inputs, so the request form is never left.
 */

const MAX_MATCHES = 8;

/**
 * Run an action from a list item. Pressing (mousedown) keeps focus in the search box so the
 * list doesn't close first; click covers keyboards and devices that only send click.
 */
function onPress(element, action) {
    element.addEventListener('mousedown', (event) => event.preventDefault());
    element.addEventListener('click', (event) => {
        event.preventDefault();
        action();
    });
}

/**
 * @param {HTMLElement} root  The element marked with data-resident-picker.
 */
export function initResidentPicker(root) {
    /** @type {{id: number, label: string, contact: ?string, search: string}[]} */
    const residents = JSON.parse(root.querySelector('[data-residents]').textContent);

    const searchInput = root.querySelector('[data-resident-search]');
    const residentIdInput = root.querySelector('[data-resident-id]');
    const optionsList = root.querySelector('[data-resident-options]');
    const newFlagInput = root.querySelector('[data-new-flag]');
    const existingSection = root.querySelector('[data-existing-section]');
    const newSection = root.querySelector('[data-new-section]');
    const newResidentName = root.querySelector('[data-new-resident-name]');

    let matches = [];
    let activeIndex = -1;

    const isOpen = () => !optionsList.classList.contains('d-none');

    function close() {
        optionsList.classList.add('d-none');
        searchInput.setAttribute('aria-expanded', 'false');
        searchInput.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    }

    function highlight(index) {
        activeIndex = index;

        optionsList.querySelectorAll('[role="option"]').forEach((option, optionIndex) => {
            const isActive = optionIndex === index;
            option.classList.toggle('active', isActive);
            option.setAttribute('aria-selected', String(isActive));

            if (isActive) {
                searchInput.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function choose(resident) {
        residentIdInput.value = resident.id;
        searchInput.value = resident.label;
        searchInput.classList.remove('is-invalid');
        close();
    }

    function showNewResident() {
        newFlagInput.value = '1';
        existingSection.classList.add('d-none');
        newSection.classList.remove('d-none');

        // Carry over what staff already typed, unless it was a chosen resident.
        if (!residentIdInput.value && searchInput.value.trim() && !newResidentName.value) {
            newResidentName.value = searchInput.value.trim();
        }

        close();
        newResidentName.focus();
    }

    function showExistingResident() {
        newFlagInput.value = '0';
        newSection.classList.add('d-none');
        existingSection.classList.remove('d-none');
        searchInput.focus();
    }

    function render() {
        const query = searchInput.value.trim().toLowerCase();
        matches = residents
            .filter((resident) => query === '' || resident.search.includes(query))
            .slice(0, MAX_MATCHES);

        optionsList.replaceChildren();

        matches.forEach((resident) => {
            const option = document.createElement('li');
            option.id = `resident-option-${resident.id}`;
            option.className = 'list-group-item list-group-item-action';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.textContent = resident.label;

            if (resident.contact) {
                const contact = document.createElement('span');
                contact.className = 'cell-sub d-block';
                contact.textContent = resident.contact;
                option.append(contact);
            }

            onPress(option, () => choose(resident));

            optionsList.append(option);
        });

        if (matches.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'list-group-item small';
            empty.textContent = 'No resident found. ';

            const addButton = document.createElement('button');
            addButton.type = 'button';
            addButton.className = 'btn btn-link btn-sm p-0 align-baseline';
            addButton.textContent = `Add "${searchInput.value.trim()}" as a new resident`;
            onPress(addButton, showNewResident);

            empty.append(addButton);
            optionsList.append(empty);
        }

        optionsList.classList.remove('d-none');
        searchInput.setAttribute('aria-expanded', 'true');
        activeIndex = -1;
    }

    searchInput.addEventListener('input', () => {
        residentIdInput.value = '';
        render();
    });

    searchInput.addEventListener('focus', render);
    searchInput.addEventListener('blur', close);

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            if (!isOpen()) {
                render();
            }

            if (matches.length > 0) {
                const step = event.key === 'ArrowDown' ? 1 : -1;
                highlight((activeIndex + step + matches.length) % matches.length);
            }
        } else if (event.key === 'Enter' && isOpen()) {
            // Never submit the whole form from the search box.
            event.preventDefault();

            if (activeIndex >= 0) {
                choose(matches[activeIndex]);
            } else if (matches.length === 1) {
                choose(matches[0]);
            }
        } else if (event.key === 'Escape' && isOpen()) {
            // Close the list without closing the whole modal.
            event.preventDefault();
            event.stopPropagation();
            close();
        }
    });

    root.querySelector('[data-show-new]').addEventListener('click', showNewResident);
    root.querySelector('[data-show-existing]').addEventListener('click', showExistingResident);
}
