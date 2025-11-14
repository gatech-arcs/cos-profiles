/**
 * @file
 */

(function ($, Drupal, once) {
  function DirectAddWidget(directAdd, settings) {
    var options = $.extend({
      title: Drupal.t('List additional actions')
    }, settings);
    this.$directAddWidget = $(directAdd);
    this.$filter = this.$directAddWidget.find('.layout-builder__direct-add__filter');

    this.$directAddWidget.on({
      'mouseleave.lbDirectAdd': $.proxy(this.hoverOut, this),
      'mouseenter.lbDirectAdd': $.proxy(this.hoverIn, this),
      'focusout.lbDirectAdd': $.proxy(this.focusOut, this),
      'focusin.lbDirectAdd': $.proxy(this.focusIn, this)
    });
  }

  function directAddClickHandler(e) {
    e.preventDefault();
    $(e.target).parent().toggleClass('open');
  }

  /**
   * Attaches the JS test behavior to label links.
   */
  Drupal.behaviors.lbDirectAdd = {
    attach: function (context, settings) {
      $(once('bind-click', '.layout-builder__add-block', context)).each(function () {
        $(this).on('click', directAddClickHandler);
        DirectAddWidget.widgets.push(new DirectAddWidget(this, settings.lbDirectAdd));
      });
    }
  };
  $.extend(DirectAddWidget, {
    widgets: []
  });
  $.extend(DirectAddWidget.prototype, {
    toggle: function toggle(show) {
      var isBool = typeof show === 'boolean';
      show = isBool ? show : !this.$directAddWidget.hasClass('open');
      this.$directAddWidget.toggleClass('open', show);
    },
    hoverIn: function hoverIn() {
      if (this.timerID) {
        window.clearTimeout(this.timerID);
      }
    },
    hoverOut: function hoverOut() {
      this.timerID = window.setTimeout($.proxy(this, 'close'), 500);
    },
    open: function open() {
      this.toggle(true);
    },
    close: function close() {
      this.toggle(false);
    },
    focusOut: function focusOut(e) {
      this.hoverOut.call(this, e);
    },
    focusIn: function focusIn(e) {
      this.hoverIn.call(this, e);
    }
  });

  Drupal.lbDirectAdd = DirectAddWidget;
})(jQuery, Drupal, once);
