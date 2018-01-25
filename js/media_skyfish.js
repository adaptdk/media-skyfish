(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.createPager = {
    attach: function (context) {
      $(".vertical-tabs__pane").each(function( index ) {
        var id = this.id;
        $("#" + id).append("<div id='" + id + "-pagination'></div>");
        var items = $("#" + id + " .details-wrapper div");
        var numItems = items.length;
        var perPage = 10;
        items.slice(perPage).hide();
        $("#" + id + "-pagination").pagination({
          items: numItems,
          itemsOnPage: perPage,
          cssStyle: "light-theme",
          onPageClick: function(pageNumber) {
            var showFrom = perPage * (pageNumber - 1);
            var showTo = showFrom + perPage;
            items.hide()
                .slice(showFrom, showTo).show();
          }
        });
      });
    }
  }

}(jQuery, Drupal, drupalSettings));