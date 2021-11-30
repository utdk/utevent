/**
 * @file
 *
 * Modal functionality for the UTEvent module.
 */

/**
 * Add modal button to AddToCal button.
 */
(function ($, Drupal) {
  $('[data-type="add_to_cal_modal"]').each(function () {
    let id = $(this).data("target");
    let options = {
      autoOpen: false,
      modal: true,
      width: 550,
      title: 'Add to Calendar'
    };
    let theDialog = $(this).dialog(options);
    // $("#" + id).keyup(function (event) {
    //   if (event.which === 13) {
    //     theDialog.dialog("open");
    //   }
    // });
    $("#" + id).click(function () {
      theDialog.dialog("open");
    });
  });
  $('.rule-text .addtocal-trigger').each(function () {
    $(this).html('Add series to calendar');
  });
})(jQuery, Drupal);
