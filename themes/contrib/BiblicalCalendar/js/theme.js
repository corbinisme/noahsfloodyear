var theme = {
    init: function() {
        console.log("Biblical Calendar theme initialized.");
        theme.calendarBottom();
    },
    calendarBottom: function() {
        if(document.querySelector('.region-calendar-bottom')){
            let calendarBottom = document.querySelector('.region-calendar-bottom');
            // check if there are child elements
            if (calendarBottom.children.length > 0) {
                // add a class to the region
                calendarBottom.classList.add('has-content');

                let calendar_bottom_header = document.createElement('div');
                    calendar_bottom_header.classList.add('calendar-bottom-header');
                calendar_bottom_header.innerHTML = '<h5>More information</h5><ul class="calendar-bottom-links"></ul>';
                // insert before the first child
                calendarBottom.insertBefore(calendar_bottom_header, calendarBottom.firstChild);  
                document.querySelectorAll('.region-calendar-bottom > *:not(.calendar-bottom-header)').forEach(function(item) {
                    item.classList.add('calendar-bottom-item');

                    let itemId = item.getAttribute('id');
                    if(itemId) {
                        let heading = item.querySelector('h2');
                        if (heading) {
                            heading.classList.add('calendar-bottom-heading');
                            let headingText = heading.textContent.trim();
                            let anchor = document.createElement('a');
                            anchor.setAttribute('href', '#' + itemId);
                            anchor.innerHTML = '<i class="fa fa-chevron-down"></i>';
                            anchor.innerHTML += ' ' + headingText;
                           
                            anchor.classList.add('calendar-bottom-anchor');
                            //append the anchor to the heading
                            heading.innerHTML = ''; // clear the heading text   
                            heading.appendChild(anchor);
                        }
                        let newHeadingLinkLi = document.createElement('li');
                        let newHeadingLink = document.createElement('a');
                        newHeadingLink.setAttribute('href', '#' + itemId);
                        newHeadingLink.textContent = heading ? heading.textContent : 'More Info';
                        newHeadingLink.classList.add('calendar-bottom-link');
                        newHeadingLinkLi.appendChild(newHeadingLink);
                        calendar_bottom_header.querySelector("ul").appendChild(newHeadingLinkLi);
                    }
                });

                calendarBottom.querySelectorAll('.calendar-bottom-link').forEach(function(item) {
                    item.addEventListener('click', function(event) {
                        event.preventDefault();

                        calendarBottom.querySelectorAll('.calendar-bottom-link').forEach(function(el) {
                            el.classList.remove('active');
                        });
                        event.target.classList.toggle("active");
                        let targetId = this.getAttribute('href').substring(1);
                        document.querySelectorAll('.calendar-bottom-item').forEach(function(el) {
                            el.classList.remove('active');
                        });
                        let targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            targetElement.classList.toggle("active");
                        }
                    });
                });

                calendarBottom.querySelectorAll('.calendar-bottom-heading a').forEach(function(item) {
                    item.addEventListener('click', function(event) {
                        event.preventDefault();
                        document.querySelectorAll('.calendar-bottom-item').forEach(function(el) {
                            el.classList.remove('active');
                        });
                        let targetId = this.getAttribute('href').substring(1);
                        let targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            targetElement.classList.toggle("active");
                        }
                        event.target.classList.toggle("active");
                    });
                });
            }
        }
    }
}

// on document ready, initialize the theme
document.addEventListener("DOMContentLoaded", function() {
    theme.init();
});
