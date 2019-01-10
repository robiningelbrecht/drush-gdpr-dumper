(function($, Drupal) {

  Drupal.behaviors.gdprDumperSummary = {
    attach: function (context, settings) {
      // Display the action in the vertical tab summary.
      $(context).find('details[data-table-summary]').drupalSetSummary(function(context) {
        return Drupal.checkPlain($(context).data('table-summary'));
      });

    }
  }

})(jQuery, Drupal);