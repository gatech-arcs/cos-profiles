import { Plugin } from 'ckeditor5/src/core';
import { Collection } from 'ckeditor5/src/utils';
import {
  Model,
  LabeledFieldView,
  addListToDropdown,
  ContextualBalloon,
  clickOutsideHandler,
  createLabeledDropdown,
} from 'ckeditor5/src/ui';
import { isWidget } from 'ckeditor5/src/widget';
import HeadingSizeEditing from './headingsizeediting';
import { UiViewModel as ViewModel } from './utils/symbolshims';

export default class HeadingSizeUI extends Plugin {

  static get pluginName() {
    return 'HeadingSizeUI';
  }

  static get requires() {
    return [ HeadingSizeEditing ];
  }

  afterInit() {
    const editor = this.editor
    const headingUi = editor.plugins.get('HeadingUI');
    if (headingUi.isEnabled) {
      // Listen for clicks in the editor.
      this.listenTo( editor.editing.view.document, 'click', () => {
        // Determine if the click was on a heading tag.
        const parentHeading = this.getSelectedHeadingElement();
        if ( parentHeading ) {
          // Build a contextual menu.
          this._balloon = editor.plugins.get( ContextualBalloon );
          // With a select list of font sizes to choose from.
          this.dropdownLabeledFieldView = new LabeledFieldView(editor.locale, createLabeledDropdown);
          // Set the label.
          const editing = editor.plugins.get(HeadingSizeEditing)
          const className = editing.findHeadingSizeClassOnElement(parentHeading);
          const headingSizes = this.editor.config.get( 'headingSizes' );
          const label = className ? Object.keys(headingSizes).find(key => headingSizes[key] === className) : Drupal.t('Font Size');
          this.dropdownLabeledFieldView.set({
            label: label,
            'class': 'black-label',
          });
          this.dropdownLabeledFieldView.fieldView.buttonView.set({
            withText: true,
          })

          // Add font size options.
          let items = new Collection();
          for(let option in headingSizes) {
            items.add({
              type: 'button',
              model: new ViewModel({
                id: headingSizes[option],
                label: option,
                withText: true,
              })
            });
          }
          addListToDropdown(this.dropdownLabeledFieldView.fieldView, items);

          // Add the contextual balloon.
          this._balloon.add({
            view: this.dropdownLabeledFieldView,
            position: this.getBalloonPositionData(),
          });

          // Close the context balloon if the user clicks outside of it.
          clickOutsideHandler({
            emitter: this.dropdownLabeledFieldView,
            activator: () => this._balloon.hasView( this.dropdownLabeledFieldView ),
            contextElements: [ this._balloon.view.element ],
            callback: () => this.hideUI()
          });

          // A font size option was clicked.
          this.dropdownLabeledFieldView.fieldView.on('execute', (event) => {
            let value = event.source.label;

            // Set the dropdown label.
            this.dropdownLabeledFieldView.label = value;
            // Add the font size to the heading model.
            this.editor.model.change((writer) => {
              const blocks = Array.from( this.editor.model.document.selection.getSelectedBlocks() );
              const headingSizes = this.editor.config.get( 'headingSizes' );
              for ( const block of blocks ) {
                writer.setAttribute('fontSize', headingSizes[value], block);
              }
            });
          });
        }
      });
    }
  }

  getSelectedHeadingElement() {
    const view = this.editor.editing.view;
    const selection = view.document.selection;
    const selectedElement = selection.getSelectedElement();

    // The selection is collapsed or some widget is selected (especially inline widget).
    if ( selection.isCollapsed || selectedElement && isWidget( selectedElement ) ) {
      return this.findHeadingElementAncestor( selection.getFirstPosition());
    }  else {
      // The range for fully selected heading is usually anchored in adjacent text nodes.
      // Trim it to get closer to the actual heading element.
      const range = selection.getFirstRange().getTrimmed();
      const startHeading = this.findHeadingElementAncestor( range.start );
      const endHeading = this.findHeadingElementAncestor( range.end );

      if ( !startHeading || startHeading != endHeading ) {
        return null;
      }

      // Check if the heading element is fully selected.
      if ( view.createRangeIn( startHeading ).getTrimmed().isEqual( range ) ) {
        return startHeading;
      } else {
        return null;
      }
    }
  }

  getBalloonPositionData() {
    const view = this.editor.editing.view;
    const viewDocument = view.document;
    let target = null;

    // Set a target position by converting view selection range to DOM.
    target = () => view.domConverter.viewRangeToDom(
      viewDocument.selection.getFirstRange()
    );

    return {
      target
    };
  }

  findHeadingElementAncestor( position) {
    return position.getAncestors().find( ( ancestor ) => this.isHeadingElement( ancestor ) ) || null;
  }

  hideUI() {
    // Make sure the focus always gets back to the editable _before_ removing the focused form view.
    // Doing otherwise causes issues in some browsers. See https://github.com/ckeditor/ckeditor5-link/issues/193.
    this.editor.editing.view.focus();
    this._balloon.remove( this.dropdownLabeledFieldView);
  }

  isHeadingElement( node) {
    const headingOptions = this.editor.config.get( 'heading.options' );
    let headings = [];
    for(let option of headingOptions) {
      if (option.model.includes('heading')) {
        headings.push(option.view);
      }
    }
    return headings.includes(node.name);
  }

}
