var dates = {

    init: function(){
 
        dates.setupSignificantDates();
        calendar.init();
    },
    setupSignificantDates: function(){
        document.querySelectorAll(".SignificantDateRow").forEach(function(row){
            //row.querySelector(".views-field-body").classList.add("hidden");
            row.querySelector(".views-field-title a").addEventListener("click", function(e){
                e.preventDefault();
                let href = e.target.href;
                let node = e.target.closest(".SignificantDateRow").querySelector(".views-field-body");
                if(!e.target.classList.contains("showing")){
                    //node.classList.remove("hidden")
                    e.target.classList.add("showing");
                    e.target.closest(".SignificantDateRow").classList.add("showing");
                } else {
                    //node.classList.add("hidden")
                    e.target.classList.remove("showing");
                    e.target.closest(".SignificantDateRow").classList.remove("showing");
                }

            })
        })
    }
}


window.addEventListener('load',
  function() {
    dates.init();
  }, false);



  var calendar = {
    init: function(){
        
        calendar.countMonthDuplicates();
        calendar.getDates();
        
       
        calendar.binding();
        
        calendar.expandContent();
        calendar.placePassover();
        calendar.placePentecostWeekCounts();
        //calendar.placeFallFeasts();
        calendar.updateThreeBoxes();
        calendar.placeHolyDays();
        
    },
    binding: function(){
       
    },
    dates: {
        "passover": null,
        "unleavenedbread": null,
        "unleavenedbreadend": null,
        "pentecost": null,
        "trumpets": null,
        "atonement": null,
        "tabernacles": null,
        "tabernaclesend": null,
        "lastgreatday": null,
    },
    updateThreeBoxes: function(){
        const math = document.getElementById("Math");
        const totaldays = math.querySelector(".TotalDays");
        const calcdays = math.querySelector(".CalcDays");
        const calculated = math.querySelector(".Calculated");

        totaldays.querySelector(".Title").innerHTML = "<u>What 247 year period from creation?</u>";
        let numCycleVals = parseInt(totaldays.querySelector(".NumCycles").innerText);
        totaldays.querySelector(".NumCycles").innerHTML = "<span class='mt-2 mb-2 btn btn-secondary text-white'>" +(numCycleVals+1) +"</span>";
        


        calcdays.querySelector(".Title").innerHTML = "<u>Which 19 year time cycle of the 13 in the 247 year period?</u>";
        calcdays.querySelector(".NumCycles").innerHTML = "<span class='mt-2 mb-2 btn btn-secondary text-white'>" + calcdays.querySelector(".NumCycles").innerText + "</span>";
        calcdays.querySelectorAll(".Title").forEach(function(el, idx){
            if(idx>0){
                if(idx==1){
                    el.innerHTML = "<u>Difference between the solar and Hebrew calendars</u>";
                } 
                if(idx==2){
                    el.innerHTML = "<u>Last year's difference equals</u>";
                }
                el.classList.add("text-center");
                el.classList.add("fullOverride");
            }
        });
        calcdays.querySelectorAll(".NumDays").forEach(function(el, idx){
            el.classList.add("text-center");

            el.classList.add("justify-content-center");
            el.classList.add("w-100");
            el.classList.add("btn-outline-secondary");
            el.classList.add("btn");
            el.classList.add("d-inline-flex");
            el.classList.add("m-0")

        });

        calculated.querySelector(".Title").innerHTML = "<u>What year in the 19 year time cycle?</u>";
        calculated.querySelector(".NumYears").innerHTML = "<span class='mt-2 mb-2 btn btn-secondary text-white'>" + calculated.querySelector(".NumYears").innerText + "</span>";

        calculated.querySelectorAll(".Title").forEach(function(el, idx){
            if(idx>0){
                if(idx==1){
                    el.innerHTML = "<u>Difference between last year and present year</u>";
                    el.classList.add("text-center");
                    el.classList.add("fullOverride");
                }

            }
        });
        const numDaysCalc = calculated.querySelector(".NumDays");
        numDaysCalc.classList.add("justify-content-center");
        numDaysCalc.classList.add("w-100");
        numDaysCalc.classList.add("btn-outline-secondary");
        numDaysCalc.classList.add("btn");
        numDaysCalc.classList.add("d-inline-flex");
        numDaysCalc.classList.add("m-0");
    },
    makeDateString: function(date){
        let dateString = date;
        dateString = dateString.substring(dateString.indexOf(",")+1, dateString.length);
        dateString = dateString.substring(0, dateString.indexOf(",")).trim()

   
        return dateString
    },
    getDates: function(){
        let gregDate = document.querySelector("input[name='gregDate'" ).value;
        gregDate = gregDate.substring(2, gregDate.length);
        const era = document.querySelector("input[name='eraType'" ).value.toUpperCase();

        const table = document.querySelector("#block-biblicalcalendar-calendardatesblock .table");
        // get a Date() object for each date
        // in the format of "Day Month, Year AD"
        calendar.dates.passover = this.makeDateString(table.querySelector(".passover").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  + era);
        calendar.dates.unleavenedbread = this.makeDateString(table.querySelector(".unleavenedbread").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.unleavenedbreadend = table.querySelector(".unleavenedbread").closest("tr").querySelector(".end").innerText;
        calendar.dates.pentecost = this.makeDateString(table.querySelector(".pentecost").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.trumpets = this.makeDateString(table.querySelector(".trumpets").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.atonement = this.makeDateString(table.querySelector(".atonement").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.tabernacles = this.makeDateString(table.querySelector(".tabernacles").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.tabernaclesend = table.querySelector(".tabernacles").closest("tr").querySelector(".end").innerText;
        calendar.dates.lastgreatday = this.makeDateString(table.querySelector(".lastgreatday").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
    },
    setHCmonthNums: function(){
        let initCounter = 1;
        document.querySelectorAll(".Cell.HCC.Month.first").forEach(function(mo){
            let orig = mo.querySelector("span").innerText;
            let monthNum = 1;
            let id = mo.getAttribute("id");
    
            switch(orig){
                case "Nisan": monthNum = 1; break;
                case "Iyar": monthNum = 2; break;
                case "Sivan": monthNum = 3; break;
                case "Tamm": monthNum = 4; break;
                case "Av": monthNum = 5; break;
                case "Elul": monthNum = 6; break;
                case "Tish": monthNum = 7; break;
                case "Chesh": monthNum = 8; break;
                case "Kis": monthNum = 9; break;
                case "Tev": monthNum = 10; break;
                case "Shev": monthNum = 11; break;
                case "Adar": monthNum = 12; break;
                case "Adar II": monthNum = 13; break;
            }
            if(orig!=""){
                mo.querySelector("span").innerHTML = "(" + monthNum + ") " + orig;
                initCounter++;
            }
        });
    },
    loadedMain: false,
    expandContent: function(){
        
        calendar.loadedMain = true;
        let parent = document.querySelector(".loadhtmlwrapper");
        
        parent.querySelectorAll(".Cell.Month").forEach(function(ce){
            let val = ce.innerHTML;
            
            if(val == "&amp;nbsp;"){
                ce.innerHTML = "&nbsp;";
            }
            calendar.findPassover();
        });

        calendar.setHCmonthNums();
        calendar.findFirstOfNisan();
        
    },
    findFirstOfNisan: function() {
        var a = document.getElementById("Nisan1");
        var b = document.getElementById("Nisan2");
        var c = document.getElementById("Nisan3");
        var d = document.getElementById("Nisan4");
        var e = document.getElementById("Nisan5");
        var f = document.getElementById("Nisan6");
        var g = document.getElementById("Nisan7");
        var LowValue = 16;
        var using = null;
        var year = document.getElementById("AMYear").value;
        if (a != null) {
            var y = parseFloat(a.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = a; }
        }
        if (b != null) {
            var y = parseFloat(b.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = b; }
        }
        if (c != null) {
            var y = parseFloat(c.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = c; }
        }
        if (d != null) {
            var y = parseFloat(d.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = d; }
        }
        if (e != null) {
            var y = parseFloat(e.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = e; }
        }
        if (f != null) {
            var y = parseFloat(f.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = f; }
        }
        if (g != null) {
            var y = parseFloat(g.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = g; }
        }
        if (using != null) {

            var u = using.parentNode;
            u.closest(".month").classList.add("nisanmonth");
            u.classList.add("nisancolumn");
            var child = u.firstChild;
            var value = u.Value;

            child = child.nextSibling;
            child = child.nextSibling;
            while (child) {
                child.style.backgroundColor = '#FFFABA';
                child.style.color = 'black';
                child = child.nextSibling;
            }
        }
    },
    placePassover: function(){
        //get passover date on GC and find
        const passoverDate = calendar.dates.passover;
        const passovverDateParts = passoverDate.split(" ");
        const passoverMonth = passovverDateParts[0];
        const passoverDay = parseInt(passovverDateParts[1]);

        const nisanMonth = document.querySelector(".nisancolumn").closest(".month");
        let nisanMonthCount = null;
        if(nisanMonth) 
            nisanMonthCount= parseInt(nisanMonth.getAttribute("data-month-count"));

        let passoverMonthNode =null;

        let totalPassoverMonthPotentials = 0;
        document.querySelectorAll(".month." + passoverMonth).forEach(function(mo){
            //console.log("counts: passover month: ",mo.getAttribute("data-month-count"), "minas", nisanMonthCount)
            if(parseInt(mo.getAttribute("data-month-count")) >= nisanMonthCount){
  
                passoverMonthNode = mo;
                totalPassoverMonthPotentials++;
            }
        })
        // if totalPassoverMonthPotentials is more than one, choose the first in the query list
        if(totalPassoverMonthPotentials > 1){
            passoverMonthNode = document.querySelector(".month." + passoverMonth);
        }

        passoverMonthNode.classList.add("passovermonth");
        let passoverMonthsGregDates = [];
        passoverMonthNode.querySelectorAll(".Cell.GC:not(.Month)").forEach(function(ce){
            
            const inner = parseInt(ce.innerText);
            passoverMonthsGregDates.push(inner);
            ce.setAttribute("data-day", inner)
        });

        //console.log("passoverMonthsGregDates", passoverMonthsGregDates) 
        // see if passoverDay is between any of the dates
        let passoverDayNode = null;
        // find which two dates in passoverMonthGregDates that passoverDay is between
        let firstDate = null;
        let secondDate = null;
        passoverMonthsGregDates.forEach(function(date){
            if(passoverDay >= date){
                firstDate = date;
            } else if(passoverDay <= date){
                secondDate = date;
            }
        });
        //console.log(passoverDay, firstDate, secondDate);
        const firstDateNode = passoverMonthNode.querySelector(".Cell.GC:not(.Month)[data-day='" + firstDate + "']");
        const firstDateColumnNode = firstDateNode.closest(".Column");
        //firstDateColumnNode.classList.add("afterContent")
        //firstDateColumnNode.setAttribute("data-name", "passover");
        
        let unleavenedbreadColumnNode = null;
        if(firstDateColumnNode.nextSibling){
            unleavenedbreadColumnNode = firstDateColumnNode.nextSibling;
        } else {
            unleavenedbreadColumnNode = firstDateColumnNode.closest(".Month").nextSibling.querySelector(".Column:nth-child(1)")
        }
        //unleavenedbreadColumnNode.classList.add("beforeContent");
        //unleavenedbreadColumnNode.classList.add("afterContent");
        //unleavenedbreadColumnNode.setAttribute("data-name", "unleavenedbread");
        
    },
    getPreviousWeek: function(count){
        
        let currentNode = document.querySelector(".pentecostcolumn_current");
        let previousNode = null;

        
        if(currentNode.previousSibling){
            previousNode = currentNode.previousSibling;
        } else {
            // go to previous month
            const previousMonthNode = currentNode.previousSibling;
            if(previousMonthNode){
                const previousMonthColumnNode = previousMonthNode.querySelector(".Column:nth-child(1)");
                previousMonthColumnNode.classList.add("pentecostcolumn_current");
                previousNode = previousMonthColumnNode
            } else {
                // go back a .Month
                const currentMonthNode = currentNode.closest(".month");
                const previousMonthNode = currentMonthNode.previousSibling;
                const prevMonColumnCount = previousMonthNode.querySelectorAll(".Column").length;
                const previousMonthColumnNode = previousMonthNode.querySelector(".Column:nth-child(" + prevMonColumnCount + ")");
                previousNode = previousMonthColumnNode;
            }
        }
        previousNode.classList.add("pentecostcolumn_current");
        previousNode.classList.add("pentecostcount");
        let newcount = count-1;
        if(newcount==0){
            newcount = '';
        }
        previousNode.classList.add("pentecostcount_" + (newcount));
        previousNode.setAttribute("data-count", (newcount));
    },
    countToPentecost: function(){
        const pentecostColumnNode = document.querySelector(".Column[data-name='prepentecost']");
        pentecostColumnNode.classList.add("pentecostcolumn_current");
        pentecostColumnNode.classList.add("pentecostcount_7");
        pentecostColumnNode.setAttribute("data-count", "7")
        let count = 7;
        for(var i=count; i>0; i--){

            this.getPreviousWeek(i);
        }
        
    },
    findNumbersInRange: function(arr, target) {
        arr.sort((a, b) => a - b); // Sort the array in ascending order
        let prev = arr[0];
        let next = arr[arr.length - 1];
      
        for (let i = 0; i < arr.length; i++) {
          if (arr[i] <= target) {
            prev = arr[i]; // Update previous number
          } else {
            next = arr[i]; // Update next number
            break; // Stop searching once we find the next number
          }
        }
      
        return [prev, next];
    },
    placeHolyDays: function(){
        // loop through calendar.dates
        Object.keys(calendar.dates).forEach(function(key){
            console.log(key, calendar.dates[key]);
            let date = calendar.dates[key];
            let dateParts = date.split(" ");
            let month = dateParts[0];
            let day = parseInt(dateParts[1]);
            let monthNode = document.querySelector(".month." + month);
            let monthCount = parseInt(monthNode.getAttribute("data-month-count"));
            let monthPotentials = [];
            document.querySelectorAll(".month." + month).forEach(function(mo){
                if(parseInt(mo.getAttribute("data-month-count")) >= monthCount){
                    monthPotentials.push(mo);
                }
            });
            let monthNodeFinal = null;
            if(monthPotentials.length > 1){
                monthNodeFinal = document.querySelector(".month." + month);
            } else {
                monthNodeFinal = monthPotentials[0];
            }
            console.log(key, monthNodeFinal)
            monthNodeFinal.classList.add(key + "month");
            


            let monthGregDates = [];
            monthNodeFinal.querySelectorAll(".Cell.GC:not(.Month)").forEach(function(ce){
                
                const inner = parseInt(ce.innerText);
                monthGregDates.push(inner);
                ce.setAttribute("data-day", inner)
            }); 
            
            let dayNode = null;
            // find which two dates in monthGregDates that day is between
            let firstDate = null;
            let secondDate = null;
            const datesResult = calendar.findNumbersInRange(monthGregDates, day);
            console.log(key, day + ":", datesResult, "in ", monthGregDates);
            firstDate = datesResult[0];
            secondDate = datesResult[1];

            let useThisDate = secondDate;
            if(firstDate == day){
                useThisDate = firstDate;
            }

            let offset = day-useThisDate;
            let percentage = offset * 100 / 7;
            // round percentage to 100th place
            percentage = Math.round(percentage * 100) / 100;
            // attach to the second date
            const secondDateColumnNode = monthNodeFinal.querySelector(".Cell.GC:not(.Month)[data-day='" + useThisDate + "']").closest(".Column");
            secondDateColumnNode.classList.add(`data_${key}_column`);
            secondDateColumnNode.setAttribute(`date_${key}`, day);
            secondDateColumnNode.setAttribute(`date_${key}_percentage`, percentage + "%");
            secondDateColumnNode.setAttribute(`date_${key}_offset`, offset);

            
            secondDateColumnNode.classList.add("holydaymonthcolumn");
            if(secondDateColumnNode.querySelector(".holydayoverlay")){

            } else {
                const holydaydiv = document.createElement("div");
                holydaydiv.classList.add("holydayoverlay");
                secondDateColumnNode.appendChild(holydaydiv);
            }
            const holydaywrapper = secondDateColumnNode.querySelector(".holydayoverlay");
            
            const thisHolyDay = document.createElement("div");
            thisHolyDay.classList.add("holyday");
            thisHolyDay.classList.add("bg-"+key);
            thisHolyDay.setAttribute("data-day", day);
            thisHolyDay.setAttribute("data-holy-day", key);
            holydaywrapper.appendChild(thisHolyDay);

            /*
            const firstDateNode = monthNodeFinal.querySelector(".Cell.GC:not(.Month)[data-day='" + firstDate + "']");
            const firstDateColumnNode = firstDateNode.closest(".Column");
            firstDateColumnNode.classList.add("afterContent")
            firstDateColumnNode.setAttribute("data-name", key);
            let secondDateNode = null;
            if(firstDateColumnNode.nextSibling){
                secondDateNode = firstDateColumnNode.nextSibling;
            } else {
                secondDateNode = firstDateColumnNode.closest(".month").nextSibling.querySelector(".Column:nth-child(1)")
            }
            secondDateNode.classList.add("beforeContent");
            secondDateNode.classList.add("afterContent");
            secondDateNode.setAttribute("data-name", key);
            // fill in all dates from firstDateColumnNode to secondDateColumnNode
            */
        });
        

    },
    countMonthDuplicates: function(){
        let count = 1;
        document.querySelectorAll("#NewCalendarContainer>div").forEach(function(mo){
            const monthName = mo.classList[0];

            mo.classList.add("month");
            mo.setAttribute("data-month", monthName);
            mo.setAttribute("data-month-count", count);
            count++;
        });
    },
    placeFallFeasts: function(){
        //console.log("placeFallFeasts");
        let atonementDates = calendar.dates.atonement.split(" ");
        let trumpetsDates = calendar.dates.trumpets.split(" ");
        let tabernaclesDates = calendar.dates.tabernacles.split(" ");
        let lastgreatdayDates = calendar.dates.lastgreatday.split(" ");
        //console.log("atonementDate", atonementDates);
        //console.log("trumpetsDate", trumpetsDates);
        //console.log("tabernaclesDate", tabernaclesDates);
        //console.log("lastgreatdayDate", lastgreatdayDates);

        const atonementMonth = atonementDates[0];
        const atonementDay = parseInt(atonementDates[1]);
        const trumpetsMonth = trumpetsDates[0];
        const trumpetsDay = parseInt(trumpetsDates[1]);
        const tabernaclesMonth = tabernaclesDates[0];
        const tabernaclesDay = parseInt(tabernaclesDates[1]);
        const lastgreatdayMonth = lastgreatdayDates[0];
        const lastgreatdayDay = parseInt(lastgreatdayDates[1]);
        
        const atonementMonthNode = document.querySelector(".month." + atonementMonth);
        const trumpetsMonthNode = document.querySelector(".month." + trumpetsMonth);
        const tabernaclesMonthNode = document.querySelector(".month." + tabernaclesMonth);
        const lastgreatdayMonthNode = document.querySelector(".month." + lastgreatdayMonth);

        let atonementMonthCount = null;
        let trumpetsMonthCount = null;
        let tabernaclesMonthCount = null;
        let lastgreatdayMonthCount = null;
        if(atonementMonthNode) 
            atonementMonthCount= parseInt(atonementMonthNode.getAttribute("data-month-count"));
        if(tabernaclesMonthNode)
            tabernaclesMonthCount= parseInt(tabernaclesMonthNode.getAttribute("data-month-count"));
        if(lastgreatdayMonthNode)
            lastgreatdayMonthCount= parseInt(lastgreatdayMonthNode.getAttribute("data-month-count"));

        let atonementMonthPotentials = [];
        let trumpetsMonthPotentials = [];
        let tabernaclesMonthPotentials = [];
        let lastgreatdayMonthPotentials = [];
        document.querySelectorAll(".month." + atonementMonth).forEach(function(mo){
            if(parseInt(mo.getAttribute("data-month-count")) >= atonementMonthCount){
                atonementMonthPotentials.push(mo);
            }
        })
        document.querySelectorAll(".month." + trumpetsMonth).forEach(function(mo){
            if(parseInt(mo.getAttribute("data-month-count")) >= trumpetsMonthCount){
                trumpetsMonthPotentials.push(mo);
            }
        });
        document.querySelectorAll(".month." + tabernaclesMonth).forEach(function(mo){
            if(parseInt(mo.getAttribute("data-month-count")) >= tabernaclesMonthCount){
                tabernaclesMonthPotentials.push(mo);
            }
        })
        document.querySelectorAll(".month." + lastgreatdayMonth).forEach(function(mo){
            if(parseInt(mo.getAttribute("data-month-count")) >= lastgreatdayMonthCount){
                lastgreatdayMonthPotentials.push(mo);
            }
        })
        let atonementMonthNodeFinal = null;
        let trumpetsMonthNodeFinal = null;
        let tabernaclesMonthNodeFinal = null;
        let lastgreatdayMonthNodeFinal = null;
        if(atonementMonthPotentials.length > 1){
            atonementMonthNodeFinal = document.querySelector(".month." + atonementMonth);
        } else {
            atonementMonthNodeFinal = atonementMonthPotentials[0];
        }
        if(trumpetsMonthPotentials.length > 1){
            trumpetsMonthNodeFinal = document.querySelector(".month." + trumpetsMonth);
        } else {
            trumpetsMonthNodeFinal = trumpetsMonthPotentials[0];
        }
        if(tabernaclesMonthPotentials.length > 1){
            tabernaclesMonthNodeFinal = document.querySelector(".month." + tabernaclesMonth);
        } else {
            tabernaclesMonthNodeFinal = tabernaclesMonthPotentials[0];
        }
        if(lastgreatdayMonthPotentials.length > 1){
            lastgreatdayMonthNodeFinal = document.querySelector(".month." + lastgreatdayMonth);
        } else {
            lastgreatdayMonthNodeFinal = lastgreatdayMonthPotentials[0];
        }
        atonementMonthNodeFinal.classList.add("atonementmonth");
        tabernaclesMonthNodeFinal.classList.add("tabernaclesmonth");
        lastgreatdayMonthNodeFinal.classList.add("lastgreatdaymonth");
        let atonementMonthsGregDates = [];
        let trumpetsMonthsGregDates = [];
        let tabernaclesMonthsGregDates = [];
        let lastgreatdayMonthsGregDates = [];
        atonementMonthNodeFinal.querySelectorAll(".Cell.GC:not(.Month)").forEach(function(ce){
            
            const inner = parseInt(ce.innerText);
            atonementMonthsGregDates.push(inner);
            ce.setAttribute("data-day", inner)
        });
        trumpetsMonthNodeFinal.querySelectorAll(".Cell.GC:not(.Month)").forEach(function(ce){
            
            const inner = parseInt(ce.innerText);
            trumpetsMonthsGregDates.push(inner);
            ce.setAttribute("data-day", inner)
        });

        tabernaclesMonthNodeFinal.querySelectorAll(".Cell.GC:not(.Month)").forEach(function(ce){
            
            const inner = parseInt(ce.innerText);
            tabernaclesMonthsGregDates.push(inner);
            ce.setAttribute("data-day", inner)
        });
        lastgreatdayMonthNodeFinal.querySelectorAll(".Cell.GC:not(.Month)").forEach(function(ce){
            
            const inner = parseInt(ce.innerText);
            lastgreatdayMonthsGregDates.push(inner);
            ce.setAttribute("data-day", inner)
        });

        // see if atonementDay is between any of the dates
        

        let atonementDayNode = null;
        // find which two dates in atonementMonthGregDates that atonementDay is between
        let atonementfirstDate = null;
        let atonementsecondDate = null;
        atonementMonthsGregDates.forEach(function(date){
            if(atonementDay >= date){
                atonementfirstDate = date;
            } else if(atonementDay <= date){
                atonementsecondDate = date;
            }
        });
        //console.log(atonementDay, atonementfirstDate, atonementsecondDate);
        const atonementfirstDateNode = atonementMonthNodeFinal.querySelector(".Cell.GC:not(.Month)[data-day='" + atonementfirstDate + "']");
        const atonementfirstDateColumnNode = atonementfirstDateNode.closest(".Column");
        atonementfirstDateColumnNode.classList.add("afterContent")
        atonementfirstDateColumnNode.setAttribute("data-name", "atonement");
        
        let atonementsecondDateNode = null;
        if(atonementfirstDateColumnNode.nextSibling){
            atonementsecondDateNode = atonementfirstDateColumnNode.nextSibling;
        } else {
            atonementsecondDateNode = atonementfirstDateColumnNode.closest(".month").nextSibling.querySelector(".Column:nth-child(1)")
        }
        //atonementsecondDateNode.classList.add("beforeContent"); 
        
        // trumpets
        let trumpetsDayNode = null;
        // find which two dates in trumpetsMonthGregDates that trumpetsDay is between
        let trumpetsfirstDate = null;
        let trumpetssecondDate = null;
        trumpetsMonthsGregDates.forEach(function(date){
            if(trumpetsDay >= date){
                trumpetsfirstDate = date;
            } else if(trumpetsDay <= date){
                trumpetssecondDate = date;
            }
        }
        );
        //console.log(trumpetsDay, trumpetsfirstDate, trumpetssecondDate);
        const trumpetsfirstDateNode = trumpetsMonthNodeFinal.querySelector(".Cell.GC:not(.Month)[data-day='" + trumpetsfirstDate + "']");
        const trumpetsfirstDateColumnNode = trumpetsfirstDateNode.closest(".Column");
        trumpetsfirstDateColumnNode.classList.add("afterContent")
        trumpetsfirstDateColumnNode.setAttribute("data-name", "feastoftrumpets");


        // tabernacles
        let tabernaclesDayNode = null;
        // find which two dates in tabernaclesMonthGregDates that tabernaclesDay is between
        let tabernaclesfirstDate = null;
        let tabernaclessecondDate = null;
        tabernaclesMonthsGregDates.forEach(function(date){
            if(tabernaclesDay >= date){
                tabernaclesfirstDate = date;
            } else if(tabernaclesDay <= date){
                tabernaclessecondDate = date;
            }
        });
        if(tabernaclesfirstDate == null){
            tabernaclesfirstDate = tabernaclesMonthsGregDates[0];
        }
        //console.log(tabernaclesDay, tabernaclesfirstDate, tabernaclessecondDate);
        // lastgreatday
        let lastgreatdayDayNode = null;
        // find which two dates in lastgreatdayMonthGregDates that lastgreatdayDay is between
        let lastgreatdayfirstDate = null;
        let lastgreatdaysecondDate = null;
        lastgreatdayMonthsGregDates.forEach(function(date){
            if(lastgreatdayDay >= date){
                lastgreatdayfirstDate = date;
            } else if(lastgreatdayDay <= date){
                lastgreatdaysecondDate = date;
            }
        });
        //console.log(lastgreatdayDay, lastgreatdayfirstDate, lastgreatdaysecondDate);
        const lastgreatdayfirstDateNode = lastgreatdayMonthNodeFinal.querySelector(".Cell.GC:not(.Month)[data-day='" + lastgreatdayfirstDate + "']");
        const lastgreatdayfirstDateColumnNode = lastgreatdayfirstDateNode.closest(".Column");
        lastgreatdayfirstDateColumnNode.classList.add("afterContent")
        lastgreatdayfirstDateColumnNode.setAttribute("data-name", "lastgreatday");

        
        
        const tabernaclesfirstDateNode = tabernaclesMonthNodeFinal.querySelector(".Cell.GC:not(.Month)[data-day='" + tabernaclesfirstDate + "']");
        const tabernaclesfirstDateColumnNode = tabernaclesfirstDateNode.closest(".Column");
        tabernaclesfirstDateColumnNode.classList.add("afterContent")
        tabernaclesfirstDateColumnNode.setAttribute("data-name", "feastoftabernacles");

        let tabernaclessecondDateNode = null;
        if(tabernaclesfirstDateColumnNode.nextSibling){
            tabernaclessecondDateNode = tabernaclesfirstDateColumnNode.nextSibling;
        } else {
            tabernaclessecondDateNode = tabernaclesfirstDateColumnNode.closest(".month").nextSibling.querySelector(".Column:nth-child(1)")
        }
        tabernaclessecondDateNode.classList.add("beforeContent");
        tabernaclessecondDateNode.classList.add("afterContent");
        // fill in all dates from tabernaclesfirstDateColumnNode to lastgreatdayFirstDateColumnNode
        this.fillInTabernacles();

        



    },
    fillInTabernacles: function(){
        // check if the last great day is in a new month or not
        const lastgreatdayfirstDateMonth = document.querySelector(".lastgreatdaymonth").getAttribute("data-month");
        const tabernaclesfirstDateColumnNode = document.querySelector(".Column[data-name='feastoftabernacles']");
        const tabernaclesFirstDateMonth = tabernaclesfirstDateColumnNode.closest(".month").getAttribute("data-month");
        const tabernaclesfirstDateColumnNodeWidth = window.getComputedStyle(tabernaclesfirstDateColumnNode).width;
        const newWidth = parseInt(tabernaclesfirstDateColumnNodeWidth.substring(0, tabernaclesfirstDateColumnNodeWidth.length-2));
        const style = document.createElement("style");
        style.id="tabernaclesStyle";

        if(lastgreatdayfirstDateMonth != tabernaclesFirstDateMonth){
            // put the overlay on the end month and count backwards
            // in the case of the months dropping to two lines
            const lastgreatdayMonthNode = document.querySelector(".Column[data-name='lastgreatday']");
            lastgreatdayMonthNode.classList.add("feastoftabernaclesAndLGDmonth");

            style.innerHTML = ".path-calendar #NewCalendarContainer .feastoftabernaclesAndLGDmonth::before{z-index:2;width: " + (newWidth-15) + "px!important;}";
            style.innerHTML += ".path-calendar #NewCalendarContainer [data-name=lastgreatday]::after{z-index:3;}";  
            style.innerHTML += ".path-calendar #NewCalendarContainer [data-name=feastoftabernacles]::after{right:.5px;}";
            
        } else {

            // still, check if the start and end are on different lines even in the same month
            // get computed position of feastoftabernaclesfirstDateColumnNode
            const position = tabernaclesfirstDateColumnNode.getBoundingClientRect();
            console.log("position", position)

            // get computed width of tabernaclesfirstDateColumnNode
            const newright = newWidth -5;
            // for this hack, just get the width of the cell until the 8th day
           
            style.innerHTML = ".path-calendar #NewCalendarContainer [data-name=feastoftabernacles]::after{z-index:2;width: " + newWidth + "px;right: -" + newright + "px;}";
            style.innerHTML += ".path-calendar #NewCalendarContainer [data-name=lastgreatday]::after{z-index:3;}";  
            
        }
        document.head.appendChild(style);


        

       

    },
    fillInColumn: function(col){
        let currentCol = col;
        let currentColName = (currentCol.hasAttribute("data-name")?currentCol.getAttribute("data-name"):"");
        while(currentColName != "lastgreatday"){
            
            if(currentCol.nextSibling){
                currentCol = currentCol.nextSibling;
            } else {
                currentCol = currentCol.closest(".month").nextSibling.querySelector(".Column:nth-child(1)")
            }
            currentColName = (currentCol.hasAttribute("data-name")?currentCol.getAttribute("data-name"):"");
            console.log("currentColName", currentColName, currentCol)
        }

        
    },
    placePentecostWeekCounts: function(){
        const pentecostNode = document.querySelector(".afterContent[data-name='prepentecost']");
        pentecostNode.classList.add("pentecostcount");
        pentecostNode.classList.add("pentecostcolumn_current");
        this.countToPentecost();
    },
    findPassover:function() {
        var a = document.getElementById("Sivan5");
        var b = document.getElementById("Sivan7");
        var c = document.getElementById("Sivan9");
        var d = document.getElementById("Sivan11");
        var g = document.getElementById("Nisan7");
        var LowValue = 16;
        var using = null;
        var year = document.getElementById("AMYear").value;
        if (a != null) {
            var y = parseFloat(a.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = a; }
        }
        if (b != null) {
            var y = parseFloat(b.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = b; }
        }

        if (c != null) {
            var y = parseFloat(c.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = c; }
        }
        if (d != null) {
            var y = parseFloat(d.getAttribute("count"))
            if (LowValue > y) { LowValue = y; using = d; }
        }
        if (g != null) {
            var w = document.getElementById("Sivan4");
            var y = parseFloat(w.getAttribute("count") - 1)
            if (LowValue > y) { LowValue = y; using = w; }
        }
        if (using != null) {
            var u = using.parentNode;
            u.classList.add("afterContent");
            u.setAttribute("data-name", "prepentecost");
            
            var child = u.firstChild;
            child = child.nextSibling;
            var GregorianDate = calendar.strip(child.innerHTML);
            //              if (g != null) {
            //                  GregorianDate -= 7;
            //              }
            var GregorianMonth = child.getAttribute("month");
            var output = GregorianMonth + ' ' + (parseFloat(GregorianDate) + 1);
            //document.getElementById("PentcostDate").innerHTML = output;
            while (child) {
                child.style.backgroundColor = '#FFFABA';
                child.style.color = 'black';
                child = child.nextSibling;
            }
            calendar.findFirstOfNisan();
            
        }
    },
    strip: function(html) {
        var tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    },


    
}


