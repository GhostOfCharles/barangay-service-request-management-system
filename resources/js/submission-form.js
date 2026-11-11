/**
 * Dynamic Submission Form (FR-10, Screens 4 and 11).
 *
 * Selecting a service type fills in its fee and category and shows its required-document checklist,
 * one upload per document (JPG, PNG, or PDF, up to 5 MB). Submit stays disabled until every
 * required document is attached. The server checks all of this again on save.
 */

const MAX_BYTES = 5 * 1024 * 1024;
const ALLOWED = ['jpg', 'jpeg', 'png', 'pdf'];

function formatPeso(amount) {
    return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatSize(bytes) {
    return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
}

/**
 * @param {HTMLElement} root  The element marked with data-submission-form.
 */
export function initSubmissionForm(root) {
    const serviceTypes = JSON.parse(root.querySelector('[data-service-types]').textContent);
    const serverErrors = JSON.parse(root.querySelector('[data-document-errors]')?.textContent || '{}');

    const select = root.querySelector('[data-service-select]');
    const feeField = root.querySelector('[data-fee]');
    const categoryField = root.querySelector('[data-category]');
    const checklist = root.querySelector('[data-checklist]');
    const title = root.querySelector('[data-checklist-title]');
    const submitButton = root.querySelector('[data-submit]');
    const submitHint = root.querySelector('[data-submit-hint]');

    function updateSubmitState() {
        const missing = [...checklist.querySelectorAll('[data-required="1"]')]
            .filter((row) => !row.querySelector('input[type="file"]').files.length);

        const ready = select.value !== '' && missing.length === 0;
        submitButton.disabled = !ready;

        if (submitHint) {
            submitHint.textContent = select.value === ''
                ? 'Select a service type first.'
                : missing.length
                    ? 'Attach all required documents to submit. Missing: ' + missing.map((row) => row.dataset.name).join(', ') + '.'
                    : '';
        }
    }

    function buildRow(document) {
        const row = window.document.createElement('li');
        row.className = 'checklist-row';
        row.dataset.required = document.required ? '1' : '0';
        row.dataset.name = document.name;

        const box = window.document.createElement('span');
        box.className = 'checklist-box';
        box.setAttribute('aria-hidden', 'true');

        const name = window.document.createElement('span');
        name.className = 'checklist-name';
        name.textContent = document.name + (document.required ? '' : ' (optional)');

        const fileInfo = window.document.createElement('span');
        fileInfo.className = 'checklist-file';

        const picker = window.document.createElement('label');
        picker.className = 'checklist-pick';
        const pickerText = window.document.createElement('span');
        pickerText.textContent = 'Choose file';
        const input = window.document.createElement('input');
        input.type = 'file';
        input.name = `documents[${document.id}]`;
        input.accept = '.jpg,.jpeg,.png,.pdf';
        input.setAttribute('aria-label', `Upload ${document.name}`);
        picker.append(pickerText, input);

        const error = window.document.createElement('div');
        error.className = 'invalid-feedback d-block w-100 m-0';
        error.textContent = serverErrors[document.id] || '';
        if (error.textContent) {
            row.classList.add('is-invalid');
        }

        input.addEventListener('change', () => {
            const file = input.files[0];
            error.textContent = '';
            row.classList.remove('is-invalid', 'is-attached');
            box.textContent = '';
            fileInfo.textContent = '';
            pickerText.textContent = 'Choose file';

            if (file) {
                const extension = file.name.split('.').pop().toLowerCase();

                if (!ALLOWED.includes(extension)) {
                    error.textContent = `${document.name}: use a JPG, PNG, or PDF file.`;
                } else if (file.size > MAX_BYTES) {
                    error.textContent = `${document.name}: the file is larger than 5 MB.`;
                }

                if (error.textContent) {
                    input.value = '';
                    row.classList.add('is-invalid');
                } else {
                    row.classList.add('is-attached');
                    box.textContent = '✓';
                    fileInfo.textContent = `${file.name} · ${formatSize(file.size)}`;
                    pickerText.textContent = 'Change';
                }
            }

            updateSubmitState();
        });

        const line = window.document.createElement('div');
        line.className = 'd-flex align-items-center gap-2 w-100';
        line.append(box, name, fileInfo, picker);

        row.classList.add('flex-wrap');
        row.append(line, error);

        return row;
    }

    function render() {
        const serviceType = serviceTypes.find((option) => String(option.id) === select.value);
        checklist.replaceChildren();

        if (!serviceType) {
            feeField.value = '';
            categoryField.value = '';
            title.textContent = 'Required Documents';
            const empty = window.document.createElement('li');
            empty.className = 'text-secondary small';
            empty.textContent = 'Select a service type to see its fee and required documents.';
            checklist.append(empty);
        } else {
            feeField.value = formatPeso(serviceType.fee);
            categoryField.value = serviceType.category;
            title.textContent = `Required Documents — ${serviceType.name}`;

            if (serviceType.documents.length === 0) {
                const none = window.document.createElement('li');
                none.className = 'text-secondary small';
                none.textContent = 'No documents are needed for this service.';
                checklist.append(none);
            }

            serviceType.documents.forEach((document) => checklist.append(buildRow(document)));
        }

        updateSubmitState();
    }

    select.addEventListener('change', render);
    render();
}
