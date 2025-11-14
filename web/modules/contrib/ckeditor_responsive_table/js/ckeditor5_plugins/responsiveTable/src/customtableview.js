import {
  createLabeledInputNumber,
  createLabeledInputText,
  LabeledFieldView,
  submitHandler,
  ButtonView,
  ToolbarSeparatorView,
  ToolbarView,
  SwitchButtonView,
  FormHeaderView,
  View
} from 'ckeditor5/src/ui';
import { IconCancel, IconCheck } from '@ckeditor/ckeditor5-icons';

export default class FormView extends View {
  constructor( locale ) {
    super( locale );

    // Create the different controls and labels that the form will need.
    this.rowsInputView = this._createInputNumber( 'Rows', 3 );
    this.columnsInputView = this._createInputNumber( 'Columns', 2 );

    this.balloonLabelView = new FormHeaderView( locale, {
      label: this.t( 'Table properties' )
    } )
    this.balloonLabelView.text = 'Table Properties';

    this.headersLabelView = new View();
    this.headersLabelView.setTemplate( {
      tag: 'span',
      attributes: {
        class: [
          'ck',
          'ck-span-label'
        ],
      },
      children: [
        {
          text: 'Table Headers'
        }
      ]
    } );

    this.headersDropdownView = this._createDropdown( 'Headers' );
    this.captionInputView = this._createInputText( 'Caption' );
    this.captionVisibleInputView = this._createSwitch( 'Caption Visible?' );

    this.saveButtonView = this._createButton( 'Save', IconCheck, 'ck-button-save' );
    // Submit type of the button will trigger the submit event on entire form when clicked
    // (see submitHandler() in render() below).
    this.saveButtonView.type = 'submit';
    this.cancelButtonView = this._createButton( 'Cancel', IconCancel, 'ck-button-cancel' );
    // Delegate ButtonView#execute to FormView#cancel
    this.cancelButtonView.delegate( 'execute' ).to( this, 'cancel' );

    this.childViews = this.createCollection( [
      this.balloonLabelView,
      this.rowsInputView,
      this.columnsInputView,
      this.headersLabelView,
      this.headersDropdownView,
      this.captionInputView,
      this.captionVisibleInputView,
      this.saveButtonView,
      this.cancelButtonView
    ] );

    this.setTemplate( {
      tag: 'form',
      attributes: {
        class: [ 'ck', 'ck-wildrose-form' ],
        tabindex: '-1'
      },
      children: this.childViews
    } );
  }

  render() {
    super.render();

    // Submit the form when the user clicked the save button or pressed enter in the input.
    submitHandler( {
      view: this
    } );
  }

  focus() {
    this.childViews.get(1).focus();
  }

  _createInputNumber( label, defaultValue ) {
    const labeledInput = new LabeledFieldView( this.locale, createLabeledInputNumber );

    labeledInput.label = label;
    labeledInput.fieldView.value = defaultValue;

    return labeledInput;
  }

  _createInputText( label ) {
    const labeledInput = new LabeledFieldView( this.locale, createLabeledInputText );

    labeledInput.label = label;
    labeledInput.placeholder = label;

    return labeledInput;
  }

  _createDropdown( label ) {

    const separator = new ToolbarSeparatorView();
    function createButtonFirst() {
      const button = new ButtonView();
      button.set( { label: 'First Row', withText: true, isOn: true } );
      return button;
    }

    function createButtonBoth() {
      const button = new ButtonView();
      button.set( { label: 'Both', withText: true } );
      return button;
    }

    const buttonFirst = createButtonFirst();
    const buttonBoth = createButtonBoth();

    const items = [ buttonFirst, separator, buttonBoth ];

    const toolbarSeparator = new ToolbarView( this.locale, createLabeledInputText, {
      header: 'first',
    });

    toolbarSeparator.label = label;
    toolbarSeparator.header = 'first';

    items.forEach( item => toolbarSeparator.items.add( item ) );

    toolbarSeparator.render();

    buttonFirst.on( 'execute', () => {
      buttonFirst.set( { isOn: true } );
      buttonBoth.set( { isOn: false } );
      toolbarSeparator.header = 'first';
    });

    buttonBoth.on( 'execute', () => {
      buttonFirst.set( { isOn: false } );
      buttonBoth.set( { isOn: true } );
      toolbarSeparator.header = 'both';
    });

    return toolbarSeparator;
  }

  _createSwitch( label ) {
    const switchButton = new SwitchButtonView( this.locale );

    switchButton.set( {
      label: label,
      withText: true,
      isOn: false
    } );
    switchButton.render();
    switchButton.on( 'execute', () => { switchButton.isOn = !switchButton.isOn } );

    return switchButton;
  }

  _createButton( label, icon, className ) {
    const button = new ButtonView();

    button.set( {
      label,
      icon,
      tooltip: true,
      class: className
    } );

    return button;
  }
}
