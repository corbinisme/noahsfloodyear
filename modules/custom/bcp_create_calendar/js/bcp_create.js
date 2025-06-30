console.log("BCP Create Calendar JS loaded");
var bcp_create = {
    currentLink: 0,
    st: window.localStorage,
    init: function() {
        console.log("BCP Create Calendar JS initialized");
        // Add any additional initialization code here
        this.binding();
        if(this.st.getItem('currentLink')) {
            this.currentLink = parseInt(this.st.getItem('currentLink'));
            console.log("Current link from storage:", this.currentLink);
            // add the active class to the link
           document.querySelector('.file-list a[data-counter="' + this.currentLink + '"]').classList.add('active');
        } else {
            this.st.setItem('currentLink', this.currentLink);
        }
    },
    fetchFileContent: function(index) {
        console.log("Fetching file content from: " + index);
        index = parseInt(index);
        document.querySelectorAll('.file-list a').forEach(function(el) {
            el.classList.remove('active');
        });
        let thisNode = document.querySelector('.file-list a[data-counter="' + index + '"]');
        
        thisNode.classList.add('active'); // Add active class to clicked link
        
        //let thisCounter = parseInt(thisNode.getAttribute('data-counter'));
        bcp_create.currentLink = index;
        bcp_create.st.setItem('currentLink', bcp_create.currentLink);

        console.log("Counter: " + bcp_create.currentLink);

        let filePath = thisNode.getAttribute('href');
        fetch(filePath)
            .then(response => response.text())
            .then(data => {
                console.log(filePath, "File content loaded:", data);
                let nextLink = bcp_create.currentLink + 1;
                console.log("Next link to fetch:", nextLink);
                bcp_create.fetchFileContent(nextLink);
                //document.querySelector('.file-list a[data-counter').click();
            })
            .catch(error => {
                console.error("Error loading file:", error);
            });
    },
    binding: function() {
        let counter = 1;
        document.querySelectorAll('.file-list a').forEach(function(link) {
            link.setAttribute('data-counter', counter);
            counter++;
        });
        document.querySelectorAll('.file-list a').forEach(function(link) {
            link.addEventListener('click', function(event, el) {
                event.preventDefault(); // Prevent default link behavior
                console.log("Link clicked:", event.target);
                var dataCounter = event.target.getAttribute('data-counter');
                console.log("Data counter:", dataCounter);
                bcp_create.fetchFileContent(dataCounter);
                
            });
        });
    },
};
// Document ready function to ensure the DOM is fully loaded before executing scripts
document.addEventListener("DOMContentLoaded", function() {
    bcp_create.init();
});
