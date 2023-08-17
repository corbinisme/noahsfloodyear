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
    document.getElementById("block-biblicalcalendar-videogreeting").classList.add("playing")
    video._video.play();
}



(function($) {
    // Backbone code in here
  
    $(document).ready(function ($) {
  
  
      var cookie = getCookie("visitgreen");
      console.log("cookie", cookie)
      if (cookie == "1") {
          /*
          $('#gsVideo').remove(); //remove video wrapper
          $('#placeholderImg').remove();
          */
          //$('.video-section').removeClass("hidden")
      } else {
          setCookie("visitgreen", "1", 30);
          
          var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth; //looking to find window size to prevent video on tablet and mobile
          console.log(width);
          if (width > 1000) { //the width value to check
  
            $('.video-section').removeClass("hidden")
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