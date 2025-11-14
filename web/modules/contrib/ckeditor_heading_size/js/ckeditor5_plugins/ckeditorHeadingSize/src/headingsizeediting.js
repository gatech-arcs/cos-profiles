import {Plugin} from "ckeditor5/src/core";
import { priorities } from 'ckeditor5/src/utils';
export default class HeadingSizeEditing extends Plugin {

  static get pluginName() {
    return 'HeadingSizeEditing';
  }

  init() {
    const editor = this.editor;

    const headingOptions = editor.config.get( 'heading.options' );
    for(let option of headingOptions) {
      if (option.model.includes('heading')) {
        // Allow headings to have the fontSize attribute in their models.
        editor.model.schema.extend(option.model, {allowAttributes: 'fontSize'});

        const classOptions = Object.values(this.editor.config.get( 'headingSizes' ));
        // Convert from the view (the HTML) to the model.
        editor.conversion.for('upcast').attributeToAttribute({
          view: classOptions,
          model: {
            key: 'fontSize',
            value: (viewElement) => {
              return this.findHeadingSizeClassOnElement(viewElement);
            },
          },
          converterPriority: priorities.low + 1,
        });
      }
    }

    // Convert from the model to the view.
    editor.conversion.for('downcast').attributeToAttribute({
      model: 'fontSize',
      view: (attributeValue, conversionApi) => {
        return {
          key: 'class',
          value: attributeValue,
        };
      },
      converterPriority: priorities.low + 1,
    });
  }

  findHeadingSizeClassOnElement(element) {
    // Determine if the headings have any heading-size-14px classes.
    const headingClasses = Object.values(this.editor.config.get( 'headingSizes' ));
    for (let className of element.getClassNames()) {
      if (headingClasses.includes(className)) {
        return className;
      }
    }
  }

}
