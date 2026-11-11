/**
 * Required Document Checklist editor on Service Types, Fees and Checklists (FR-14, Screen 13):
 * "+ Add required document" adds a row; "Remove" deletes one. Rows are saved with the form.
 */
export function initChecklistEditor(root) {
    const list = root.querySelector('[data-checklist-rows]');
    const template = root.querySelector('[data-checklist-template]');
    let nextIndex = list.querySelectorAll('[data-checklist-row]').length;

    function bindRemove(row) {
        row.querySelector('[data-remove-row]').addEventListener('click', () => {
            row.remove();
        });
    }

    list.querySelectorAll('[data-checklist-row]').forEach(bindRemove);

    root.querySelector('[data-add-row]').addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('[data-checklist-row]');

        row.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace('__INDEX__', String(nextIndex));
        });
        row.querySelectorAll('[id]').forEach((field) => {
            field.id = field.id.replace('__INDEX__', String(nextIndex));
        });
        row.querySelectorAll('[for]').forEach((label) => {
            label.htmlFor = label.htmlFor.replace('__INDEX__', String(nextIndex));
        });
        nextIndex += 1;

        bindRemove(row);
        list.append(row);
        row.querySelector('input[type="text"]').focus();
    });
}
