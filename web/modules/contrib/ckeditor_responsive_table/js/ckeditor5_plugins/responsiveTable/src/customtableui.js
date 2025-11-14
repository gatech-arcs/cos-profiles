/**
 * @file registers the customTable toolbar button and binds functionality to it.
 */

import { Plugin } from 'ckeditor5/src/core';
import { IconCaption } from '@ckeditor/ckeditor5-icons';
import { ButtonView, ContextualBalloon, clickOutsideHandler } from 'ckeditor5/src/ui';
import icon from '../../../../icons/responsivetable.svg';
import FormView from './customtableview';
import ToggleCustomTableCaptionCommand from './togglecustomtablecaptioncommand.js';


const hideCaption = 'hideCaption';
export default class CustomTableUI extends Plugin {

  init() {
    const editor = this.editor

    editor.model.schema.extend( 'caption', { allowAttributes: hideCaption } );
    //model-view conversion
    editor.conversion.attributeToAttribute({
      model: {
        key: hideCaption,
      },
      view: {
        key: 'class',
        value: ['hide-caption'],
      }
    });

    editor.model.schema.extend('tableCell', { allowAttributes: ['setScopeAttr'] });

    //model-view conversion
    editor.conversion.attributeToAttribute({
      model: {
        name: 'tableCell',
        key: 'setScopeAttr',
      },
      view: {
        name: 'th',
        key: 'scope',
      }
    });
    editor.conversion.attributeToAttribute({
      model: {
        name: 'tableCell',
        key: 'setScopeAttr',
      },
      view: {
        name: 'td',
        key: 'scope',
      }
    });

    // Set up the toggle caption contextual button.
    editor.commands.add('toggleCustomTableCaptionCommand', new ToggleCustomTableCaptionCommand(editor));
    const t = editor.t;

    editor.ui.componentFactory.add('toggleCustomTableCaption', locale => {
      const command = editor.commands.get('toggleCustomTableCaptionCommand');
      const view = new ButtonView(locale);

      view.set({
        icon: IconCaption,
        tooltip: true,
        isToggleable: true
      });

      view.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      view.bind('label').to(command, 'value', value => value ? t('Toggle caption off') : t('Toggle caption on'));

      this.listenTo(view, 'execute', () => {
        editor.execute( 'toggleCustomTableCaptionCommand' );
      });

      return view;
    });


    // Create the balloon and the form view.
    this._balloon = this.editor.plugins.get(ContextualBalloon);
    this.formView = this._createFormView();

    editor.ui.componentFactory.add('customTable', (locale) => {
      const button = new ButtonView(locale);

      button.label = 'Responsive Table';
      button.tooltip = true;
      button.icon = icon;

      // Show the UI on button click.
      this.listenTo(button, 'execute', () => {
        this._showUI();
      });

      return button;
    });

    let doc = editor.model.document;

    this.listenTo(doc, 'change:data', () => {
      const changes = doc.differ.getChanges();
      editor.model.change(writer => {
        for (const change of changes) {
          if (change.type == 'insert' && (change.name == 'tableCell' || change.name == 'tableRow')) {
            const table = change.position.findAncestor('table');
            this._setAttribute(table, writer);
          }
          if (change.type == 'insert' && change.name == 'table') {
            const table = change.position.nodeAfter;
            writer.setAttribute('headingRows', 1, table);
            this._setAttribute(table, writer);
          }
        }
      });
    });
  }

  _createFormView() {
    const editor = this.editor;
    const formView = new FormView(editor.locale);

    // Execute the command after clicking the "Save" button.
    this.listenTo(formView, 'submit', () => {
      // Grab values from the abbreviation and title input fields.
      const rows_number = formView.rowsInputView.fieldView.element.value;
      const columns_number = formView.columnsInputView.fieldView.element.value;
      const headers = formView.headersDropdownView.header;
      const caption_text = formView.captionInputView.fieldView.element.value;
      const captionVisible = formView.captionVisibleInputView.isOn;

      if (rows_number === '') {
        formView.rowsInputView.errorText = 'Number of rows must be a number greater than 0.';
      } else if (columns_number === '') {
        formView.columnsInputView.errorText = 'Number of columns must be a number greater than 0.';
      } else if (typeof headers === 'undefined') {
        alert('Please select a Header option.');
      } else if (caption_text === '') {
        formView.captionInputView.errorText = 'Please enter a caption.';
      } else if (caption_text.length > 200) {
        formView.captionInputView.errorText = 'The caption length must be down that 200 characters.';
      } else {
        const tableUtils = this.editor.plugins.get('TableUtils');
        editor.model.change(writer => {
          let table = tableUtils.createTable(writer, { rows: rows_number, columns: columns_number, headingColumns: headers === 'both', headingRows: true });

          this._setAttribute(table, writer);

          editor.model.insertObject(table, null, null, { findOptimalPosition: 'auto' });
          writer.setSelection(writer.createPositionAt(table.getNodeByPath([0, 0, 0]), 0));
          const newCaptionElement = writer.createElement('caption');
          writer.appendText(caption_text, newCaptionElement);

          if (!captionVisible) {
            writer.setAttribute(hideCaption, 'hide-caption', newCaptionElement);
          }

          editor.model.insertContent(newCaptionElement, table, 'end');
          editor.model.schema.extend('table', { allowAttributes: 'class' });
        });

        // Hide the form view after submit.
        this._hideUI();
      }
    });

    // Hide the form view after clicking the "Cancel" button.
    this.listenTo(formView, 'cancel', () => {
      this._hideUI();
    });

    // Hide the form view when clicking outside the balloon.
    clickOutsideHandler({
      emitter: formView,
      activator: () => this._balloon.visibleView === formView,
      contextElements: [this._balloon.view.element],
      callback: () => this._hideUI()
    });

    return formView;
  }

  _showUI() {
    this._balloon.add({
      view: this.formView,
      position: this._getBalloonPositionData(),
      // Close other contextual elements.
      singleViewMode: true
    });

    this.formView.focus();
  }

  _hideUI() {
    // Clear the input field values and reset the form.
    this._balloon.remove(this.formView);

    this.formView = this._createFormView();

    // Focus the editing view after inserting the abbreviation so the user can start typing the content
    // right away and keep the editor focused.
    this.editor.editing.view.focus();
  }

  _getBalloonPositionData() {
    const view = this.editor.editing.view;
    const viewDocument = view.document;
    let target = null;

    // Set a target position by converting view selection range to DOM
    target = () => view.domConverter.viewRangeToDom(viewDocument.selection.getFirstRange());

    return {
      target
    };
  }

  _setAttribute(table, writer) {
    const tableChildren = Array.from(table.getChildren());
    const headingRows = table.getAttribute('headingRows');
    const headingColumns = table.getAttribute('headingColumns');

    for (let i = 0; i < tableChildren.length; ++i) {
      let rowChildren = Array.from(tableChildren[i].getChildren());
      if (typeof headingRows !== 'undefined' && i < headingRows) {
        for (let j = 0; j < rowChildren.length; ++j) {
          writer.setAttribute('setScopeAttr', 'col', rowChildren[j]);
        }
        continue;
      } else if (typeof headingColumns !== 'undefined') {
        for (let j = 0; j < headingColumns; ++j) {
          if (typeof rowChildren[j] !== 'undefined') {
            writer.setAttribute('setScopeAttr', 'row', rowChildren[j]);
          }
        }
      }
    }
  }
}
