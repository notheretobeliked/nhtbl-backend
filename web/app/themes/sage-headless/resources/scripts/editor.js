/**
 * @see {@link https://bud.js.org/extensions/bud-preset-wordpress/editor-integration/filters}
 */
roots.register.filters('@scripts/filters');

/**
 * Extend core/group with section behaviour, reveal and parallax attributes.
 *
 * Schema is registered globally so the attributes serialise in the markup
 * regardless of post type. The inspector UI below is gated to the project
 * (portfolio) CPT so authors of other post types don't see the panel.
 */
wp.hooks.addFilter(
  'blocks.registerBlockType',
  'nhtbl/group-extension-attributes',
  (settings, name) => {
    if (name !== 'core/group') return settings;

    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        behavior: { type: 'string', default: 'normal' },
        minHeight: { type: 'string', default: 'auto' },
        contentAlign: { type: 'string', default: 'center' },
        reveal: { type: 'string', default: 'none' },
        revealDirection: { type: 'string', default: 'up' },
        revealStagger: { type: 'number', default: 60 },
        parallax: { type: 'boolean', default: false },
      },
    };
  },
);

/**
 * Section / reveal / parallax inspector panels on core/group, project CPT only.
 */
const groupInspector = wp.compose.createHigherOrderComponent(
  (BlockEdit) => (props) => {
    const el = wp.element.createElement;
    const { Fragment } = wp.element;

    if (props.name !== 'core/group') {
      return el(BlockEdit, props);
    }

    const postType = wp.data.useSelect(
      (select) => select('core/editor')?.getCurrentPostType(),
      [],
    );

    if (postType !== 'project') {
      return el(BlockEdit, props);
    }

    const { attributes, setAttributes } = props;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl } = wp.components;

    return el(
      Fragment,
      null,
      el(BlockEdit, props),
      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: 'Section behaviour', initialOpen: false },
          el(SelectControl, {
            label: 'Scroll behaviour',
            help: 'Stick: pins to viewport; subsequent sections slide over it.',
            value: attributes.behavior || 'normal',
            options: [
              { label: 'Normal', value: 'normal' },
              { label: 'Sticky-stack', value: 'stick' },
            ],
            onChange: (value) => setAttributes({ behavior: value }),
          }),
          el(SelectControl, {
            label: 'Minimum height',
            value: attributes.minHeight || 'auto',
            options: [
              { label: 'Auto (content height)', value: 'auto' },
              { label: 'Full screen (100vh)', value: 'screen' },
              { label: 'Half screen (50vh)', value: 'half' },
            ],
            onChange: (value) => setAttributes({ minHeight: value }),
          }),
          el(SelectControl, {
            label: 'Content alignment',
            help: 'How content sits inside the section. Stretch makes children fill the height (e.g. a column with an image gallery).',
            value: attributes.contentAlign || 'center',
            options: [
              { label: 'Center (default)', value: 'center' },
              { label: 'Top', value: 'top' },
              { label: 'Bottom', value: 'bottom' },
              { label: 'Stretch (fill height)', value: 'stretch' },
            ],
            onChange: (value) => setAttributes({ contentAlign: value }),
          }),
        ),
        el(
          PanelBody,
          { title: 'Reveal animation', initialOpen: false },
          el(SelectControl, {
            label: 'Trigger',
            value: attributes.reveal || 'none',
            options: [
              { label: 'None', value: 'none' },
              { label: 'Scroll-locked (reveals as you scroll)', value: 'scroll-locked' },
              { label: 'Once on enter (plays once in view)', value: 'once-on-enter' },
            ],
            onChange: (value) => setAttributes({ reveal: value }),
          }),
          attributes.reveal && attributes.reveal !== 'none'
            ? el(SelectControl, {
                label: 'Direction',
                value: attributes.revealDirection || 'up',
                options: [
                  { label: 'Slide up + fade', value: 'up' },
                  { label: 'Slide from left + fade', value: 'from-left' },
                  { label: 'Fade only', value: 'fade-only' },
                ],
                onChange: (value) => setAttributes({ revealDirection: value }),
              })
            : null,
          attributes.reveal && attributes.reveal !== 'none'
            ? el(RangeControl, {
                label: 'Per-word delay (ms)',
                value: attributes.revealStagger ?? 60,
                onChange: (value) => setAttributes({ revealStagger: value }),
                min: 0,
                max: 300,
                step: 10,
              })
            : null,
        ),
        el(
          PanelBody,
          { title: 'Parallax', initialOpen: false },
          el(ToggleControl, {
            label: 'Enable parallax scroll',
            help: 'Each direct child of this group moves at a different scroll speed. A Columns block is treated as transparent — each Column inside becomes one parallax unit instead. Wrap multiple blocks in a sub-group to bundle them as one unit.',
            checked: !!attributes.parallax,
            onChange: (value) => setAttributes({ parallax: !!value }),
          }),
        ),
      ),
    );
  },
  'groupInspector',
);

wp.hooks.addFilter('editor.BlockEdit', 'nhtbl/group-inspector', groupInspector);

/**
 * Reflect min-height / sticky cue on the editor's block wrapper while authoring
 * project items. Pure styling — does not affect saved markup.
 */
const groupEditorClasses = wp.compose.createHigherOrderComponent(
  (BlockListBlock) => (props) => {
    const el = wp.element.createElement;
    if (props.name !== 'core/group') return el(BlockListBlock, props);

    const { attributes } = props;
    const extra = [];
    if (attributes.minHeight === 'screen') extra.push('editor-min-screen');
    else if (attributes.minHeight === 'half') extra.push('editor-min-half');
    if (attributes.behavior === 'stick') extra.push('editor-sticky');
    if (attributes.contentAlign && attributes.contentAlign !== 'center') {
      extra.push(`editor-align-${attributes.contentAlign}`);
    }

    if (!extra.length) return el(BlockListBlock, props);

    return el(BlockListBlock, {
      ...props,
      className: `${props.className || ''} ${extra.join(' ')}`.trim(),
    });
  },
  'groupEditorClasses',
);

wp.hooks.addFilter('editor.BlockListBlock', 'nhtbl/group-editor-classes', groupEditorClasses);

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
if (import.meta.webpackHot) import.meta.webpackHot.accept(console.error);
