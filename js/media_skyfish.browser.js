/**
 * @file
 * Handles the JS for the file browser.
 *
 * Note that this does not currently, support multiple file selection.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.media_skyfish = {
    attach: function (context, settings) {

      var active_class = 'media-skyfish-active';

      if ($('.media-skyfish-list img', context).length > 1) {
        $('.media-skyfish-list img', context).once().click(function () {
          var image = $(this).attr('data-image');
          var checkbox = 'input[name=' + image + ']';

          uncheck_skyfish_checkboxes(image);

          $(checkbox).click();
          if ($(checkbox).is(':checked')) {
            $(this).addClass(active_class);
          }
          else {
            $(this).removeClass(active_class);
          }
        });
      }

      function uncheck_skyfish_checkboxes(image) {
        $('.media-skyfish-checkbox').each(function (index, value) {
          var this_image = $(this).attr('name');
          if ($(this).is(':checked')) {
            $('img[data-image=' + this_image + ']').removeClass(active_class);
            $(this).trigger('mousedown');
          }
        });
      }
    }

  };

}(jQuery));
