var calwelcome = {
    init: function(){
        console.log("cal welcome")
    }
}

var video = null;


function removeVideo(){
    document.querySelector('.gsHome').remove();
}
function playVid(){
    //alert("play");
    document.getElementById('placeholderImg').remove();
    video._video.play();
}



(function($) {
    // Backbone code in here
  
    $(document).ready(function ($) {
  
  
      var cookie = getCookie("visitgreen")
      if (cookie == "1" && false) {
          $('#gsVideo').remove(); //remove video wrapper
          $('#placeholderImg').remove();
      } else {
          setCookie("visitgreen", "1", 30);
          var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth; //looking to find window size to prevent video on tablet and mobile
          console.log(width);
          if (width > 1000 || true) { //the width value to check
  
              video = seeThru.create('#movingAlphaDemo', {
                start: 'external',
                width: 448,
                height: 649
              });
              document.getElementById('movingAlphaDemo').addEventListener("ended", function () { //done with video?
                  document.getElementById('gsVideo').remove(); //remove video wrapper
                  document.getElementById('placeholderImg').remove(); //remove video placeholder
              });
            } else { //if the width is less than the number above, act like nothing happened
                  document.getElementById('gsVideo').remove(); // no video
            }
          }
      });
  
      // Sticky "Spread the Word" script
      
  })(jQuery);

calwelcome.init();
console.log("welcome")