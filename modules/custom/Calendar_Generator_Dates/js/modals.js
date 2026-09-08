(function (Drupal, once) {
  Drupal.behaviors.stickySidebarWrapper = {
    attach: function (context, settings) {
      once('calendarPage', '.node--type-calendar', context).forEach(function (element) {
        // Initialize sticky sidebar wrapper behavior
        const modals = element.querySelectorAll(".modal");
        modals.forEach(function(modal){
          document.body.appendChild(modal);
        });
      });
    }
  };
})(Drupal, once);