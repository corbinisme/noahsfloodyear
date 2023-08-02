var calendarnav = {
    currentYear: 1,
    init: function(){
        calendarnav.binding();
    },
    getCurrentYear: function(){
        let year = document.querySelector(".calendar_nav .currentyear").value;
        return year;

    },
    getCurrentEra: function(){
        let era = document.querySelector(".calendar_nav .currentEra").value.toLowerCase();
        return era;
    },
    updateYear: function(dir){
        
        let year = calendarnav.getCurrentYear();
        let era = calendarnav.getCurrentEra();
        //console.log("move", dir, era, year)
        if(dir =="prev"){
            if(era !="bc"){
                year--;
                if(era=="am"){
                    if(year<1){
                        year = 1;
                    }
                }
                if(era=="ad"){
                    if(year<1){
                        year = 1;
                        era = "bc"
                    }
                }
            } else {
                //bc moves other way
                year++;
                if(year<=0){
                    year = 1;
                    era="ad";
                }
            }
        } else {
            // next
            if(era !="bc"){
                year++;
                if(era=="am"){
                    if(year>6090){
                        year = 6090;
                    }
                }
                if(era=="ad"){
                    if(year>2044){
                        year = 2044;
                    }
                }
            } else {
                //bc moves other way
                year--;
                if(year>4046){
                    year = 4046;
                }
                if(year==0){
                    year = 1;
                    era="ad";
                }
            }
        }

        let newurl = "/calendar/" + era + "/" + year;
        
        window.location.href = newurl;
        
    },
    setCurrentYear: function(year){
        this.currentYear = year;
    },
    generateBtn:function(e){
        let year = calendarnav.getCurrentYear();
            let era = calendarnav.getCurrentEra();
            //console.log("generate", year, era);
            let newurl = "/calendar/" + era + "/" + year;
            window.location.href = newurl;
    },
    setDefault: function(){
        const eraDropdown = document.querySelector(".calendar_nav .currentEra");
        const yearSelector = document.querySelector(".calendar_nav .currentyear");
        const location = window.location.href;
        const locationArray = location.split("/");
        const era = locationArray[locationArray.length-2].toUpperCase();
        const year = locationArray[locationArray.length-1];
        eraDropdown.value = era;
        yearSelector.value = year;

    },
    binding: function(){
        //functionality of the nav
        //console.log("binding nav for calendar page");
        calendarnav.setDefault();

        document.querySelector(".calendar_nav .generateBtn").addEventListener("click", function(e){
            e.preventDefault();
            calendarnav.generateBtn()
        });

        document.querySelectorAll(".yearToggle").forEach(function(nav){
            nav.addEventListener("click", function(e){
                let dir = e.target.getAttribute("data-dir");
                calendarnav.updateYear(dir);
                
            })

        })

        
    },
}
window.onload = function(){
    calendarnav.init()
    console.log("calendar nav loaded")
}
