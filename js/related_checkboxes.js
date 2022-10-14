/**
 * @file
 * "Related checkboxes" library file.
 */
(function (Drupal, $) {
  /**
   * Sets state of "Alternative contact language" checkbox based on "Preferred contact language" state.
   */
  Drupal.behaviors.oeContactFormsRelatedCheckboxes = {
    attach: function (context) {
      let prefered_language_checkbox = $(context).find('input[name="corporate_fields[optional_fields][oe_preferred_language]"]');
      let alternative_language_checkbox = $(context).find('input[name="corporate_fields[optional_fields][oe_alternative_language]"]');

      // Disable "Alternative contact language" if "Preferred contact language" is disabled after page load.
      if (prefered_language_checkbox.prop('checked') !== true) {
        alternative_language_checkbox.attr('disabled', true);
      }

      // Disable/enable "Alternative contact language" based on "Preferred contact language" state.
      prefered_language_checkbox.once().click({checkbox: alternative_language_checkbox}, function(e) {
        e.data.checkbox.attr('disabled', !$(this).prop('checked'));
        if (!$(this).prop('checked')) {
          e.data.checkbox.prop('checked', false);
        }
      });
    },
  };

})(Drupal, jQuery);
