import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

wp.blocks.registerBlockType('tebuto/widget', {
    title: __('Tebuto Widget', 'tebuto'),
    description: __('Fügt das Tebuto-Widget hinzu.', 'tebuto'),
    icon: 'admin-generic',
    category: 'widgets',
    attributes: {
        backgroundColor: {
            type: 'string',
            default: '#ffffff',
        },
        border: {
            type: 'boolean',
            default: false,
        },
    },
    edit({ attributes, setAttributes }) {
        const blockProps = useBlockProps();

        return (
            <div {...blockProps}>
                <h3>{__('Tebuto Widget', 'tebuto')}</h3>
                <div>
                    <label>{__('Hintergrundfarbe:', 'tebuto')}</label>
                    <input
                        type="color"
                        value={attributes.backgroundColor}
                        onChange={(e) => setAttributes({ backgroundColor: e.target.value })}
                    />
                </div>
                <div>
                    <label>
                        <input
                            type="checkbox"
                            checked={attributes.border}
                            onChange={(e) => setAttributes({ border: e.target.checked })}
                        />
                        {__('Rahmen anzeigen', 'tebuto')}
                    </label>
                </div>
            </div>
        );
    },
    save() {
        // Der Block speichert keinen statischen Code, sondern wird dynamisch gerendert
        return null;
    },
});
