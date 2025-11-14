import { Command } from 'ckeditor5/src/core.js';

/**
 * The toggle table caption command.
 *
 *  either adds or removes the table caption of a selected table (depending on whether the caption is present or not),
 *  removes the table caption if the selection is anchored in one.
 */
export default class ToggleCustomTableCaptionCommand extends Command {


  /**
   * @inheritDoc
   */
  refresh() {
    const editor = this.editor;
    const tableElement = this._getSelectionAffectedTable(editor.model.document.selection);

    this.isEnabled = tableElement;

    // Check if a caption already exists.
    const modelCaptionElement = this._getCaptionFromTableModelElement(tableElement);
    const figcaptionElement = this.editor.editing.mapper.toViewElement(modelCaptionElement);

    if (!figcaptionElement) {
      this.value = false;
    } else if (figcaptionElement.hasClass('hide-caption')) {
      this.value = false;
    } else {
      this.value = true
    }
  }

  /**
   * Executes the command.
   *
   * @fires execute
   */
  execute() {
    const tableElement = this._getSelectionAffectedTable(this.editor.model.document.selection);
    const modelCaptionElement = this._getCaptionFromTableModelElement(tableElement);
    const figcaptionElement = this.editor.editing.mapper.toViewElement(modelCaptionElement);
    const editingView = this.editor.editing.view;
    // If a caption doesn't exists, create one.
    if (!figcaptionElement) {
      this.editor.model.change(writer => {
        const model = this.editor.model;

        // Create the new caption element.
        const newCaptionElement = writer.createElement('caption');
        model.insertContent(newCaptionElement, tableElement, 'end');
        writer.setSelection(newCaptionElement, 'in');
        this.value = true;
      });

    } else {

      editingView.scrollToTheSelection();

      // If the caption already exists, toggle it on or off.
      this.editor.model.change(writer => {
        if (figcaptionElement.hasClass('hide-caption')) {
          writer.removeAttribute('hideCaption', modelCaptionElement);
          this.value = false;
        } else {
          writer.setAttribute('hideCaption', 'hide-caption', modelCaptionElement);
          this.value = true;
        }
      });
    }

    this.editor.editing.view.focus();
    return editingView;

  }

  /**
  * Depending on the position of the selection we either return the table under cursor or look for the table higher in the hierarchy.
  */
  _getSelectionAffectedTable(selection) {
    const selectedElement = selection.getSelectedElement();

    // Is the command triggered from the `tableToolbar`?
    if (selectedElement && selectedElement.is('element', 'table')) {
      return selectedElement;
    }

    return selection.getFirstPosition().findAncestor('table');
  }

  /**
  * Returns the caption model element from a given table element. Returns `null` if no caption is found.
  *
  * @param tableModelElement Table element in which we will try to find a caption element.
  */
  _getCaptionFromTableModelElement(tableModelElement) {
    if (tableModelElement) {
      for (const node of tableModelElement.getChildren()) {
        if (node.is('element', 'caption')) {
          return node;
        }
      }
    }

    return null;
  }
}
