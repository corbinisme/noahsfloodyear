/* get options from the markup data attributes */
console.log("loaded swiperinit.js");
var swiper= {
  swipers: [],
  createMulti: function(node){

    let tempSwipe = new Swiper(node, {
      // Optional parameters
      direction: 'horizontal',
    
      // If we need pagination
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
    
      slidesPerView: 1,
      breakpoints: {  
        320: {
          slidesPerView: 1,
          spaceBetween: 20
        },

        900: {
          slidesPerView: 2,
          spaceBetween: 30
        },

        1324: {
          slidesPerView: 3,
          spaceBetween: 40
        },
      },
    
      // Navigation arrows
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
    
    
    });
    swiper.swipers.push(tempSwipe);
  },
  createSingle: function(node){
    let swipersingle = new Swiper(node, {
      // Optional parameters
      direction: 'horizontal',
    
      // If we need pagination
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
    
    
      // Navigation arrows
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
    
    
    });
  },
  buildControls: function(node){

    if(node.querySelector(".swiper-pagination")){

    } else {
      let stringyPager = `
      <!-- If we need pagination -->
      <div class="swiper-pagination"></div>`;
      node.insertAdjacentHTML("beforeend", stringyPager);
    }
    if(node.querySelector(".swiper-button-prev")){
    } else {
      let stringyButton = `
      <!-- If we need navigation buttons -->
      <div class="swiper-button-prev"></div>
      <div class="swiper-button-next"></div>`;
      node.insertAdjacentHTML("beforeend", stringyButton);
    }


    
  },
  scanForSwipers: function(){
    if(document.querySelector(".swiper")){
      document.querySelectorAll(".swiper").forEach(function(swipe){
        let classes = swipe.classList;

        swiper.buildControls(swipe);

        if(classes.contains("multi")){
          swiper.createMulti(swipe);
        } else {
          swiper.createSingle(swipe);
        }
      })
    }
  },
  init: function(){
    swiper.scanForSwipers();
  }
}


window.onload = function() {
   swiper.init();
 };





  