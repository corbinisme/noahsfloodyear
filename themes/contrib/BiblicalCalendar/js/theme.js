

(function (Drupal, once) {
	Drupal.behaviors.biblicalCalendarSearchIcon = {
		attach(context) {
			const icons = once('biblical-calendar-search-icon', 'a.search-icon', context);

			icons.forEach((icon) => {
				icon.addEventListener('click', (event) => {
					// Add search icon click handling here.
                    console.log("click")
					event.preventDefault();
                    const searchForm = context.querySelector('.region-topbar .search-block-form');
					if (searchForm) {
						searchForm.classList.toggle('active');
					}
				});
			});
		},
	};
})(Drupal, once);
