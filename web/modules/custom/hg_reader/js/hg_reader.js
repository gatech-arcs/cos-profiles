(function ($, Drupal) {
  Drupal.behaviors.hg_reader = {
    attach: function (context, settings) {
      if ($('#edit-hg-group').length > 0) {
        $.Fastselect.defaults.placeholder = 'Choose a group or groups';
        $('#edit-hg-group').fastselect();

        // Remove no group marker when other groups are selected.
        $('#edit-hg-group').change(function() {
          if ($('#edit-hg-group :selected').length > 1) {
            $('#edit-hg-group :selected[value="0"]').removeAttr('selected');
            $('.form-item-hg-group div[data-value="0"]').remove();
            $('.fstSelected').eq(0).removeClass('fstSelected');
          }
        });
      }
    }
  };
})(jQuery, Drupal);
