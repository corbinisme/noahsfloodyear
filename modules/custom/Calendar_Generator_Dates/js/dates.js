var dates = {
    loopy:null,
    init: function(){
 
        dates.findDifferenceWithLastYear();
        dates.setupSignificantDates();
        calendar.init();
        
    },
    scrapeDataFromHTML: function(){
        const parentNode = document.getElementById("NewCalendarContainer");
        let months = parentNode.querySelectorAll(".month");
        const AMyear = parseInt(document.getElementById("AMYear").value);
        
        let columnData = [];
        months.forEach(function(mo){
            // get the month name from the node
            
            const monthName = mo.getAttribute("data-month");
            const monthCount = parseInt(mo.getAttribute("data-month-count"));
            
            let dateArr = [];
            mo.querySelectorAll(".Column").forEach(function(col){
                // get the dates from the column
                let weekquery = col.querySelectorAll(".weekgrid");
                let gcDate = weekquery[0].getAttribute("data-date");

                let hebMonth ="";
                if(col.querySelector(".Month.first.HCC")){
                    hebMonth = col.querySelector(".Month.first.HCC").querySelector("span").innerText;

                }
                let temp = {
                    GC: gcDate,
                    hebMonth: hebMonth,
                }
                dateArr.push(temp);
            });

            columnData.push({

                month: monthName,
                monthCount: monthCount,
                dates: dateArr
            });

        });

        dates.yearData = [
                {
                    AM: AMyear, 
                    data: columnData
                }
        ];

        console.log("yearData", dates.yearData);
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
    },
    findDifferenceWithLastYear: function(){

        let st = window.localStorage;
        if(st.getItem("importChartThree" && false)){

            let diffNode = document.querySelector(".diffWithLastYear");
            if(diffNode){
                let hasValue  = true;
                if(diffNode.innerHTML==""){
                    hasValue = false;
                }
            
                if(!hasValue){
                    let math = document.querySelector("#Math");
                    if(math){
                        let numdays= math.querySelector(".Calculated .NumDays");
                        if(numdays){
                            let numval = parseInt(numdays.innerHTML);
                            let amYear = document.getElementById("AMYear").value;
                            

                            let url = "/calendarupdate";

                            console.log("will update " + amYear + " to " + numval, " | ", url);
                            let data = {
                                "year": amYear,
                                "value": numval
                            }
                            fetch(url, {
                                method: 'POST', // Specify the request method
                                body: JSON.stringify(data), // Convert the data to a JSON string
                                headers: {
                                'Content-Type': 'application/json' // Set the content type to JSON
                                }
                            })
                            .then(resp=>resp.json())
                            .then(dat=>{
                                console.log("response:",dat);
                                //dates.goToNext(amYear);
                            })
                        }
                    }
                } else {
                    console.log("already has value")
                }
            } else {
                // end loop
                st.removeItem("importChartThree");
            }
        }

    },
    goToNext: function(amYear){
        let amInt = parseInt(amYear);
        amInt++;
        let newUrl = "/calendar/am/"+ amInt;
        window.location.href = newUrl;

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
        //calendar.additionalLegend();
        calendar.placePassover();
        calendar.placePentecostWeekCounts();

        calendar.updateThreeBoxes();
        calendar.labelDates();
        calendar.placeHolyDays();
        
    },
    labelDates: function(){

        let year = null;
        let era = null;
        if(document.getElementById("input[name='gregDate']")){
            let yearstring = document.getElementById("input[name='gregDate']").value;
            // remove the first two characters
            year = yearstring.substring(2, yearstring.length);
            // get the first two characters
            era = yearstring.substring(0, 2);
        } else {
            let d = new Date();
            year = d.getFullYear();

            era = "AD";
        }
        // get the dates parsed on each column
        document.querySelectorAll("#NewCalendarContainer .Column .GC:not(.Month)").forEach(function(ce){
            //console.log("this is",ce.innerText);
            let col = ce.closest(".Column");
            let day = ce.innerText;
            let month = col.closest(".month").getAttribute("data-month");

            // get date from string 
            let datestring = month + " " + day + ", " + year;
            // get date object
            let dateObj = new Date(datestring);
            // start at beginning of week
            dateObj.setDate(dateObj.getDate() -7);

            //console.log("date", datestring);
            col.setAttribute("data-datestring", datestring);


            let divvy = document.createElement("div");
            divvy.classList.add("weekgrid");
            divvy.setAttribute("data-date", datestring);
            for(let i=1; i<=7; i++){
                let daydiv = document.createElement("div");
                daydiv.classList.add("daygrid");
                // add one day to the dateObj
                //console.log(datestring, "setting i", i)
                // add one day to the dateObj
                dateObj.setDate(dateObj.getDate() + 1);


                let dateDisplay = dateObj.toLocaleString('en-us', {month: 'short'}) + " " + dateObj.getDate();
                daydiv.setAttribute("data-date", dateObj.toDateString());
                daydiv.setAttribute("data-day", dateDisplay);
                daydiv.setAttribute("data-day-number", dateObj.getDate());
                divvy.appendChild(daydiv);
            }

            // insert this before .HCC:not(.Month)
            let targetCol = col.querySelector(".Cell.GC:not(.Month)");
            col.insertBefore(divvy, targetCol);

            
        });
        
    },
    additionalLegend: function(){
       let hcc=  document.querySelector(".HCC.label");
       let sabbathLegend = document.createElement("div");
       sabbathLegend.classList.add("SabbathLegend");
       sabbathLegend.classList.add("label");
       sabbathLegend.innerHTML = "Total # of Sabbaths";
        hcc.parentNode.insertBefore(sabbathLegend, hcc.nextSibling);

    },
    binding: function(){
        if(document.querySelector(".mobileToggle")){
            document.querySelector(".mobileToggle").addEventListener("click", function(e){
                e.preventDefault();
                document.querySelector("body").classList.toggle("mobileOpen");

                let sidebar = document.querySelector(".page-node-type-calendar article .layout__region--first");
                sidebar.classList.toggle("open");
                if(document.querySelector("body").classList.contains("mobileOpen")){

                    document.querySelector(".mobileToggle").classList.add("open");
                } else {
                    document.querySelector(".mobileToggle").classList.remove("open");
                }
            });
        }
        if(document.querySelector(".page-node-type-calendar .legend")){
            document.querySelectorAll(".page-node-type-calendar .legend .form-check-input").forEach(function(input){
               // event listener
               input.addEventListener("change", function(e){
                   const body = document.querySelector("body");
                   let id = e.target.id;

                   console.log("toggling", id, e.target.checked);
                   if(e.target.checked){
                       body.classList.remove(id);
                   } else {
                       body.classList.add(id);
                   }
               });
            });
            document.querySelectorAll(".page-node-type-calendar .legend .form-check-label").forEach(function(input){
               // event listener on hover
                input.addEventListener("mouseover", function(e){
                     const body = document.querySelector("body");
                     let forid = e.target.getAttribute("for");
                     body.classList.add("hover-" + forid);
                });
                input.addEventListener("mouseout", function(e){
                     const body = document.querySelector("body");
                     let forid = e.target.getAttribute("for");
                     body.classList.remove("hover-" + forid);
                });
               
            });
        }
        if(document.querySelector(".page-node-type-calendar .yearAction")){
            document.querySelectorAll(".page-node-type-calendar .yearAction").forEach(function(a){
                a.addEventListener("click", function(e){
                    e.preventDefault();
                    let id = e.target.getAttribute("href");
                    // get the last chars after the last  / in the href
                    let yearDir = parseInt(e.target.getAttribute("data-dir"));
                    let currentYear = parseInt(document.getElementById("AMYear").value);
                    let newYear = currentYear + yearDir;
                    if(newYear < 1){    
                        newYear = 1;
                    }
                    window.location.href = "../../../calendar/date/am/" + newYear;
                })
            })
        }
        if(document.querySelector(".page-node-type-calendar .view-faqs")){
            document.querySelectorAll(".page-node-type-calendar .view-faqs .views-field-title a").forEach(function(a){
                a.addEventListener("click", function(e){
                    e.preventDefault();
                    let id = e.target.getAttribute("href");
                    // get the last chars after the last  / in the href
                    id = id.substring(id.lastIndexOf("/") + 1);
                    document.querySelectorAll(".page-node-type-calendar .view-faqs .views-field-title a").forEach(function(aa){ 
                        aa.classList.remove("active");
                    });
                    e.target.classList.add("active");

                    document.querySelectorAll(".view-faqs .content-pane").forEach(function(pane){
                        if(pane.getAttribute("id") === id){
                            pane.classList.add("active");
                        } else {
                            pane.classList.remove("active");
                        }
                    });

                })
            })
        }
       if(document.getElementById("omnibox-submit")){
            document.getElementById("omnibox-submit").addEventListener("click", function(e){
                e.preventDefault();
                let inputYear = document.getElementById("omnibox-input").value;
                let inputEra = document.getElementById("omnibox-select").value;
                if(inputYear && inputEra){
                    let fetchURL ="";
                    if(inputEra.toLowerCase() == "am"){
                        fetchURL = `/jsonapi/node/calendar?filter[field_am_year]=${inputYear}`;
                    } else {
                        fetchURL = `/jsonapi/node/calendar?filter[field_gregorianyear]=${inputYear}&filter[field_gc_era]=${inputEra}`;
                    }
                    console.log("fetching", fetchURL);
                    fetch(fetchURL)
                        .then(response => response.json())
                        .then(data => {
                            console.log("data", data);
                            if(data.data && data.data.length > 0){
                                let calendarData = data.data[0];
                                console.log("calendarData", calendarData);
                                let field_am_year = calendarData.attributes.field_am_year;
                                //console.log("amYear", amYear, "gregYear", gregYear, "era", era);
                                window.location.href = `/calendar/date/am/${field_am_year}`;
                            } else {
                                console.error("No data found for the given year and era.");
                            }
                        })
                        .catch(error => {
                            console.error("Error fetching calendar data:", error);
                        });
                    
                }
            });
        }
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
        // it might have "Sat, April 3, 4046|BC"
        // or "April 3, 4046|AD"
        // check how many commas there are: 1 or 2
        const commaCount = (dateString.match(/,/g) || []).length;
        if (commaCount === 1) {
            // Format: "April 3, 4046|BC" -> return "April 3"
            dateString = dateString.split(",")[0].trim();
        } else if (commaCount === 2) {
            // Format: "Sat, April 3, 4046|BC" -> return "April 3"
            dateString = dateString.split(",")[1].trim();
        }

        return dateString
    },
    getDates: function(){
        let gregDate = document.querySelector("input[name='gregDate'" ).value;
        gregDate = gregDate.substring(2, gregDate.length);
        const era = document.querySelector("input[name='eraType'" ).value.toUpperCase();
        console.log("getting gregdate")
        const table = document.querySelector(".block-calendar-dates-block .table");
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
                
                //child.style.backgroundColor = '#FFFABA';
                //child.style.color = 'black';
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
        console.log("passoverMonth", passoverMonth, "passoverDay", passoverDay);
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
            // Try both ".Month" and ".month" in case of class name differences
            let monthNode = firstDateColumnNode.closest(".Month") || firstDateColumnNode.closest(".month");
            if (monthNode && monthNode.nextSibling) {
                unleavenedbreadColumnNode = monthNode.nextSibling.querySelector(".Column:nth-child(1)");
            }
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
        pentecostColumnNode.classList.add("border_pentecost");
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
        //console.log("!!!!!!! here we need to account for the placement of the holy day being in another month, as the date shown is the end of the week")
        Object.keys(calendar.dates).forEach(function(key){
            //console.log(key, calendar.dates[key]);
            let date = calendar.dates[key];
            let dateParts = date.split(" ");
            let month = dateParts[0];
            let day = parseInt(dateParts[1]);
            // make a date obj to calculate?
            let dateObj = new Date(date);
            let previousMonth = null;

            if(day<7){
                // need to check previous month as well

                // subtract 7 days from dateObj
                dateObj.setDate(dateObj.getDate() - 7);
                let previousMonth = dateObj.toLocaleString('en-us', {month: 'short'});
                //console.log("previousMonth", previousMonth);

            }
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
            //console.log(key, monthNodeFinal)
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
            //console.log(key, day + ":", datesResult, "in ", monthGregDates);
            firstDate = datesResult[0];
            secondDate = datesResult[1];

            //console.log("first", firstDate, "second", secondDate)
            let useThisDate = secondDate;
            if(firstDate == day){
                useThisDate = firstDate;
            }

            // attach to the second date
            const secondDateColumnNode = monthNodeFinal.querySelector(".Cell.GC:not(.Month)[data-day='" + useThisDate + "']").closest(".Column");
            secondDateColumnNode.classList.add(`data_${key}_column`);
            secondDateColumnNode.setAttribute(`date_${key}`, day);

            secondDateColumnNode.classList.add("holydaymonthcolumn");
            
            let weekgrid = secondDateColumnNode.querySelector(".weekgrid");
            if(weekgrid){
            weekgrid.querySelectorAll(".daygrid").forEach(function(dg){
                let dgDate = dg.getAttribute("data-date");
                //console.log(dgDate, date)
                let dgDateParts = dgDate.split(" ");
                let dgDay = parseInt(dgDateParts[2]);
                if(dgDay == day){
                    dg.classList.add("holyday");
                    dg.classList.add("bg-"+key);
                }
            });
            }

            /*
            if(secondDateColumnNode.querySelector(".holydayoverlay")){

            } else {
                const holydaydiv = document.createElement("div");
                holydaydiv.classList.add("holydayoverlay");
                // get date of this column
                secondDateColumnNode.appendChild(holydaydiv);
            }
            const holydaywrapper = secondDateColumnNode.querySelector(".holydayoverlay");
            
            const thisHolyDay = document.createElement("div");
            thisHolyDay.classList.add("holyday");
            thisHolyDay.classList.add("bg-"+key);
            thisHolyDay.setAttribute("data-day", day);
            thisHolyDay.setAttribute("data-holy-day", key);
            holydaywrapper.appendChild(thisHolyDay);

            */
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
        calendar.fillInTabernacles();
        //calendar.fillInUnleavenedBread();
        
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

    fillInTabernacles: function(){
        let tabernaclesStartDate = calendar.dates.tabernacles;
        let tabernaclesStartDateObj = new Date(tabernaclesStartDate);
        
        let tabStart  = document.querySelector(".daygrid[data-day='" + tabernaclesStartDate + "']");
        if(tabStart){
            tabStart.classList.add("bg-tabernacles");
        }
        // loop through the dates of tabernacles
        for(let i=1;i<7;i++){
            //console.log("setting tabernacles date", i)
            tabernaclesStartDateObj.setDate(tabernaclesStartDateObj.getDate() + 1);
            let month = tabernaclesStartDateObj.toLocaleString('en-us', {month: 'short'});
            let day = tabernaclesStartDateObj.getDate();
            //console.log("tabernacles date", month, day);
            document.querySelector(".daygrid[data-day='" + month + " " + day + "']").classList.add("bg-tabernacles");
        }

        let unleavenedbreadDate = calendar.dates.unleavenedbread;
        let unleavenedbreadDateObj = new Date(unleavenedbreadDate);
        let unleavenedbreadStart  = document.querySelector(".daygrid[data-day='" + unleavenedbreadDate + "']");
        if(unleavenedbreadStart){
            unleavenedbreadStart.classList.add("bg-unleavenedbread");
        }
        // loop through the dates of unleavened bread
        for(let i=1;i<7;i++){
            //console.log("setting unleavened bread date", i)
            unleavenedbreadDateObj.setDate(unleavenedbreadDateObj.getDate() + 1);
            let month = unleavenedbreadDateObj.toLocaleString('en-us', {month: 'short'});
            let day = unleavenedbreadDateObj.getDate();
            //console.log("unleavened bread date", month, day);
            document.querySelector(".daygrid[data-day='" + month + " " + day + "']").classList.add("bg-unleavenedbread");
        }
        
        // check if the last great day is in a new month or not
        /*
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
            //console.log("position", position)

            // get computed width of tabernaclesfirstDateColumnNode
            const newright = newWidth -5;
            // for this hack, just get the width of the cell until the 8th day
           
            style.innerHTML = ".path-calendar #NewCalendarContainer [data-name=feastoftabernacles]::after{z-index:2;width: " + newWidth + "px;right: -" + newright + "px;}";
            style.innerHTML += ".path-calendar #NewCalendarContainer [data-name=lastgreatday]::after{z-index:3;}";  
            
        }
        document.head.appendChild(style);
        */


        


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
            //console.log("currentColName", currentColName, currentCol)
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
                //child.style.backgroundColor = '#FFFABA';
                //child.style.color = 'black';
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


