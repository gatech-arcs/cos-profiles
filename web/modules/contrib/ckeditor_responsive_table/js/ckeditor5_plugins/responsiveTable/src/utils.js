/**
 * Depending on the position of the selection we either return the table under cursor or look for the table higher in the hierarchy.
 */
export function getSelectionAffectedTable( selection ) {
  const selectedElement = selection.getSelectedElement();

  // Is the command triggered from the `tableToolbar`?
  if ( selectedElement && selectedElement.is( 'element', 'table' ) ) {
    return selectedElement;
  }

  return selection.getFirstPosition().findAncestor( 'table' );
}

/**
 * Returns the caption model element from a given table element. Returns `null` if no caption is found.
 *
 * @param tableModelElement Table element in which we will try to find a caption element.
 */
export function getCaptionFromTableModelElement( tableModelElement ) {
  for ( const node of tableModelElement.getChildren() ) {
    if ( node.is( 'element', 'caption' ) ) {
      return node;
    }
  }

  return null;
}

/**
 * Returns the caption model element for a model selection. Returns `null` if the selection has no caption element ancestor.
 *
 * @param selection The selection checked for caption presence.
 */
export function getCaptionFromModelSelection( selection ) {
  const tableElement = getSelectionAffectedTable( selection );

  if ( !tableElement ) {
    return null;
  }

  return getCaptionFromTableModelElement( tableElement );
}
