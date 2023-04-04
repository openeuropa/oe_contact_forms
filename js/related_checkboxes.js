/**
 * @file
 * "Related checkboxes" library file.
 */
(function (Drupal, $, once) {
  'use strict';

  /**
   * Sets state of "Alternative contact language" checkbox based on "Preferred contact language" state.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for oeContactFormsRelatedCheckboxes.
   */
  Drupal.behaviors.oeContactFormsRelatedCheckboxes = {
    attach: function (context) {
      once('oe-contact-forms', 'input[name="corporate_fields[optional_fields][oe_preferred_language]"]', context).forEach((preferred_language_checkbox) => {
        let alternative_language_checkbox = context.querySelector('input[name="corporate_fields[optional_fields][oe_alternative_language]"]');

        // Disable "Alternative contact language" if "Preferred contact language" is disabled after page load.
        if (preferred_language_checkbox.checked !== true) {
          alternative_language_checkbox.disabled = true;
        }

        preferred_language_checkbox.addEventListener('click', function () {
          if (!this.checked) {
            alternative_language_checkbox.checked = false;
            // @Todo is this even needed? It seems to work without.
            $(alternative_language_checkbox).trigger('change');
          }
          alternative_language_checkbox.disabled = !this.checked;
        });
      });
    },
  };

})(Drupal, jQuery, once);
