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
        calendar.updateThreeBoxes();
        
    },
    binding: function(){
       
    },
    dates: {
        "passover": null,
        "unleavenedbread": null,
        "pentecost": null,
        "trumpets": null,
        "atonement": null,
        "tabernacles": null,
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
        calendar.dates.pentecost = this.makeDateString(table.querySelector(".pentecost").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.trumpets = this.makeDateString(table.querySelector(".trumpets").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.atonement = this.makeDateString(table.querySelector(".atonement").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
        calendar.dates.tabernacles = this.makeDateString(table.querySelector(".tabernacles").closest("tr").querySelector(".start").innerText + ", " + gregDate + "|"  +era);
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
            console.log("counts: passover month: ",mo.getAttribute("data-month-count"), "minas", nisanMonthCount)
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

        console.log("passoverMonthsGregDates", passoverMonthsGregDates) 
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
        console.log(passoverDay, firstDate, secondDate);
        const firstDateNode = passoverMonthNode.querySelector(".Cell.GC:not(.Month)[data-day='" + firstDate + "']");
        const firstDateColumnNode = firstDateNode.closest(".Column");
        firstDateColumnNode.classList.add("afterContent")
        firstDateColumnNode.setAttribute("data-name", "passover");
        
        let unleavenedbreadColumnNode = null;
        if(firstDateColumnNode.nextSibling){
            unleavenedbreadColumnNode = firstDateColumnNode.nextSibling;
        } else {
            unleavenedbreadColumnNode = firstDateColumnNode.closest(".Month").nextSibling.querySelector(".Column:nth-child(1)")
        }
        unleavenedbreadColumnNode.classList.add("beforeContent");
        unleavenedbreadColumnNode.classList.add("afterContent");
        unleavenedbreadColumnNode.setAttribute("data-name", "unleavenedbread");
        
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


