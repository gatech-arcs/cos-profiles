/**
 * @file Contains shims to avoid breaking older versions of CKEditor 5.
 */

import { ViewModel } from 'ckeditor5/src/ui';

export const UiViewModel = typeof ViewModel !== 'undefined' ? ViewModel : null;
