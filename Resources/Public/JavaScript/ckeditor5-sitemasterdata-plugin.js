import { Plugin } from '@ckeditor/ckeditor5-core';
import { addListToDropdown, createDropdown, ViewModel } from '@ckeditor/ckeditor5-ui';
import { Collection } from '@ckeditor/ckeditor5-utils';

/**
 * CKEditor 5 plugin: insert master data placeholders.
 *
 * Adds a dropdown button to the toolbar. The placeholder list is read from
 * editor.config.siteMasterdataPlaceholders (populated by the PHP event listener).
 * Button label and tooltip also come from config and are translated server-side.
 */
class SiteMasterdataPlaceholders extends Plugin {
    static get pluginName() {
        return 'SiteMasterdataPlaceholders';
    }

    init() {
        const editor       = this.editor;
        const placeholders = editor.config.get('siteMasterdataPlaceholders') ?? [];

        if (!placeholders.length) {
            return;
        }

        const dropdownLabel   = editor.config.get('siteMasterdataDropdownLabel')   || 'Placeholders';
        const dropdownTooltip = editor.config.get('siteMasterdataDropdownTooltip') || 'Insert master data placeholder';

        editor.ui.componentFactory.add('siteMasterdataPlaceholders', locale => {
            const dropdownView = createDropdown(locale);

            dropdownView.buttonView.set({
                label:    dropdownLabel,
                withText: true,
                tooltip:  dropdownTooltip,
            });

            // Build list items from configuration
            const items = new Collection(
                placeholders.map(({ label, value }) => ({
                    type: 'button',
                    model: new ViewModel({ withText: true, label, value }),
                }))
            );

            addListToDropdown(dropdownView, items);

            // Insert selected placeholder at cursor position
            dropdownView.on('execute', evt => {
                editor.model.change(writer => {
                    const position = editor.model.document.selection.getFirstPosition();
                    writer.insertText(evt.source.value, position);
                });
                editor.editing.view.focus();
            });

            return dropdownView;
        });
    }
}

export { SiteMasterdataPlaceholders };
export default { SiteMasterdataPlaceholders };
