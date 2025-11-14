import {Plugin} from "ckeditor5/src/core";
import HeadingSizeUi from "./headingsizeui";
import HeadingSizeEditing from "./headingsizeediting";

export default class HeadingSize extends Plugin {

  static get requires() {
    return [ HeadingSizeUi, HeadingSizeEditing ];
  }

  static get pluginName() {
    return 'HeadingSize';
  }
}

