var dates = {
    loopy:null,
    thisAMYear: null,
    init: function(){
 
        dates.thisAMYear = document.getElementById("AMYear").value;
        document.querySelector("body").classList.add("amyear-" + dates.thisAMYear);
        
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

var newcalendar = {
    init: function(){
        // check if it is the new calendar
        if(document.getElementById("total-calendar-wrapper")){
            newcalendar.setup();
            newcalendar.stickySidebar();
        }
    },
    resizeStickySidebar: function(){
        const sidebar = document.querySelector(".block-system-main-block .layout__region--first");
        if(sidebar){
            const wrapper = sidebar.querySelector(".sticky-sidebar-wrapper");
            if(wrapper){
                const sidebarWidth = sidebar.offsetWidth;
                wrapper.style.width = sidebarWidth + "px";
            }
        }
    },
    stickySidebar: function(){
        const sidebar = document.querySelector(".block-system-main-block .layout__region--first");
        if(sidebar){
            console.log("making sticky sidebar");
            // set width on resize
            window.addEventListener("resize", function(){
                newcalendar.resizeStickySidebar();
            });
            // wrap the contents of the sidebar in a div
            const wrapper = document.createElement("div");
            wrapper.classList.add("sticky-sidebar-wrapper");
            while (sidebar.firstChild) {
                wrapper.appendChild(sidebar.firstChild);
            }
            sidebar.appendChild(wrapper);
            newcalendar.resizeStickySidebar();
            // make this sidebar sticky once the user scrolls past its original position
            
            
            
            const originalOffsetTop = sidebar.offsetTop;
            // prevent the sticky sidebar from overlapping the footer
            const footer = document.querySelector("footer");
            const footerOffsetTop = footer.offsetTop;


            window.addEventListener("scroll", function(){
                const scrollY = window.pageYOffset;
                const sidebarWrapper = sidebar.querySelector(".sticky-sidebar-wrapper");
                const sidebarHeight = sidebarWrapper ? sidebarWrapper.offsetHeight : sidebar.offsetHeight;
                const maxSticky = footerOffsetTop - sidebarHeight;
                if (scrollY > originalOffsetTop && scrollY < maxSticky) {
                    sidebar.classList.add("sticky-sidebar");
                    sidebarWrapper.style.position = "fixed";
                    sidebarWrapper.style.top = "0";
                } else if (scrollY >= maxSticky) {
                    sidebar.classList.remove("sticky-sidebar");
                    sidebarWrapper.style.position = "absolute";
                    sidebarWrapper.style.top = (footerOffsetTop - sidebarHeight) + "px";
                } else {
                    sidebar.classList.remove("sticky-sidebar");
                    sidebarWrapper.style.position = "";
                    sidebarWrapper.style.top = "";
                }
            });

        }
    },
    setup: function(){
        
        // find pentecost date
        const dayNode = document.querySelector(".daylist .bg-pentecost").closest(".day");
        if(dayNode){
            const day = dayNode.getAttribute("data-day");
            const month = dayNode.getAttribute("data-month");
            //console.log("pentecost", month, day);
            // create a date object with this month and day
            const pentecostDate = new Date(month + " " + day + ", " + new Date().getFullYear());
            //console.log("pentecostDate", pentecostDate);  
            // find all the pentecost week counts
            // subtract 7 days from pentecost date 7 times 
            for(let i=1; i<=7; i++){
                let weekDate = new Date(pentecostDate);
                weekDate.setDate(weekDate.getDate() - (7 * i));
                // find the element with this date
                const weekDateString = weekDate.toDateString();
                // get the date value
                // get the month value
                const dateValue = weekDate.getDate();
                const monthValue = weekDate.toLocaleString('en-us', {month: 'long'});
                //console.log("weekDateString", weekDateString, dateValue, monthValue);
                const weekNode = document.querySelector(`.daylist .day[data-day='${dateValue}'][data-month='${monthValue}']`);
                if(weekNode){
                    //get parent
                    const targetNodeTop = weekNode.closest(".gregorian");
                    const targetNode = targetNodeTop.querySelector(".month");
                    if(targetNode){

                        // add the week count
                        const weekCountDiv = document.createElement("div");
                        weekCountDiv.classList.add("pentecost-week-count");
                        weekCountDiv.innerText = i;
                        targetNode.appendChild(weekCountDiv);
                    }
                }
            } 
        }
    }
}


window.addEventListener('load',
  function() {
    dates.init();
    newcalendar.init();
  }, false);


  var calendar = {
    init: function(){
        
        calendar.countMonthDuplicates();
        calendar.getDates();
        
        calendar.expandContent();
        //calendar.additionalLegend();
        calendar.placePassover();
        calendar.placePentecostWeekCounts();

        calendar.updateThreeBoxes();
        calendar.labelDates();
        calendar.placeHolyDays();
        calendar.binding();
        calendar.addDaysToHebrew();
        calendar.addDaystoSolar();
        
    },

    addDaysToHebrew: function(){
        //console.log("hebrew add days")

        //label the hebrew date
        document.querySelectorAll(".page-node-type-calendar .hcc-wrapper").forEach(function(wrapper){
            const valueCell = wrapper.querySelector(".HCC.Cell:not(.Month");
            if(valueCell){
                let hccDate = parseInt(valueCell.textContent);
                let col = wrapper.closest(".Column");
                col.setAttribute("data-hcc-date", hccDate)
            }
        });
        document.querySelectorAll(".page-node-type-calendar .hcc-wrapper").forEach(function(wrapper){
            let hebrewWeek = document.createElement("div");
            hebrewWeek.classList.add("weekgrid-hcc");
            hebrewWeek.classList.add("bg-HCC");
            hebrewWeek.classList.add("d-flex")
            const valueCell = wrapper.querySelector(".HCC.Cell:not(.Month");
            let thisSabbathDay = 0;
            if(valueCell){
                thisSabbathDay = parseInt(valueCell.innerText);
                
                if(thisSabbathDay>=7){
                    let startVal = thisSabbathDay-6;
                    for (var i=startVal; i<thisSabbathDay; i++){
                        
                        const dayGrid = document.createElement("div");
                        dayGrid.classList.add("dayGrid");
                        dayGrid.innerText = i;
                        hebrewWeek.append(dayGrid);
                        
                    }
                } else {
                    // calculate the difference
                    
                    // now pick up the remainder
                    let remaining = 7-thisSabbathDay;
                    //console.log("remaining", remaining);
                    //get the previous week's value
                    let col = wrapper.closest(".Column");
                    let previousColDate = "";
                    if(col.previousSibling){
                        let date = col.previousSibling.getAttribute("data-hcc-date")
                        previousColDate = parseInt(date);
                        //console.log("date", previousColDate)
                    } else {
                        //console.log("no previous column")
                        // get previous month
                        let lastMonth = col.closest(".month").previousSibling;
                        // find the last column
                        let lastCol = lastMonth.querySelector(".Column:last-child");
                        let date = parseInt(lastCol.getAttribute("data-hcc-date"));
                        //console.log("but", date)
                        previousColDate = date;

                    }

                    for(var j=1; j<=remaining; j++){
                        let newVal = j+previousColDate;
                        //console.log("running", newVal)
                        let dayGridprev= document.createElement("div");
                        dayGridprev.classList.add("dayGrid");
                        dayGridprev.innerText = newVal;
                        hebrewWeek.append(dayGridprev);
                    }

                    for(var i=1; i<thisSabbathDay; i++){
                        let dayGrid = document.createElement("div");
                        dayGrid.classList.add("dayGrid");
                        dayGrid.innerText = i;
                        hebrewWeek.append(dayGrid);
                        
                    }
                }
       
            }
            
            wrapper.append(hebrewWeek);
        })
    },
    addDaystoSolar: function(){
        document.querySelectorAll(".page-node-type-calendar .sc-wrapper").forEach(function(wrapper){
            const valueCell = wrapper.querySelector(".SC.Cell:not(.Month");
            if(valueCell){
                let scDate = parseInt(valueCell.textContent);
                let col = wrapper.closest(".Column");
                col.setAttribute("data-sc-date", scDate)
            }
        });

        document.querySelectorAll(".page-node-type-calendar .sc-wrapper").forEach(function(wrapper){
            let col = wrapper.closest(".Column");
            let scVal = parseInt(col.getAttribute("data-sc-date"));
            //console.log("scVal", scVal)
            let weekGrid = document.createElement("div");
            weekGrid.classList.add("weekgrid-sc");
            weekGrid.classList.add("d-flex");
            weekGrid.classList.add("bg-SC")

            if(scVal>7){
                let startVal = scVal-6;
                    for (var i=startVal; i<scVal; i++){
                        
                        const dayGrid = document.createElement("div");
                        dayGrid.classList.add("dayGrid");
                        dayGrid.innerText = i;
                        weekGrid.append(dayGrid);
                        
                    }
            } else {
                
                let remaining = 7-scVal;
                    //console.log("remaining", remaining);
                    //get the previous week's value
                let col = wrapper.closest(".Column");
                let previousColDate = "";
                if(col.previousSibling){
                    let date = col.previousSibling.getAttribute("data-sc-date")
                    previousColDate = parseInt(date);
                } else {
                    
                    let lastMonth = col.closest(".month").previousSibling;
                        // find the last column
                    if(lastMonth){
                        let lastCol = lastMonth.querySelector(".Column:last-child");
                        let date = parseInt(lastCol.getAttribute("data-sc-date"));
                        //console.log("but", date)
                        previousColDate = date;
                    }
                }

                if(previousColDate!=""){
                    for(var j=1; j<=remaining; j++){
                        let newVal = j+previousColDate;
                        //console.log("running", newVal)
                        let dayGridprev= document.createElement("div");
                        dayGridprev.classList.add("dayGrid");
                        dayGridprev.innerText = newVal;
                        weekGrid.append(dayGridprev);
                    }
                }   

                for(var i=1; i<scVal; i++){
                    let dayGrid = document.createElement("div");
                    dayGrid.classList.add("dayGrid");
                    dayGrid.innerText = i;
                    weekGrid.append(dayGrid);
                    
                }


            }
            wrapper.append(weekGrid);
        });
            

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
            divvy.classList.add("GC")
            divvy.setAttribute("data-date", datestring);
            for(let i=1; i<=6; i++){
                let daydiv = document.createElement("div");
                daydiv.classList.add("daygrid");
                // add one day to the dateObj
                //console.log(datestring, "setting i", i)
                // add one day to the dateObj
                dateObj.setDate(dateObj.getDate() + 1);

                let thisDay = "";
                switch(i){
                    case 1: thisDay = "S"; break;
                    case 2: thisDay = "M"; break;
                    case 3: thisDay = "T"; break;
                    case 4: thisDay = "W"; break;
                    case 5: thisDay = "T"; break;
                    case 6: thisDay = "F"; break;
                    default : thisDay = "Sat"; break;
                }

                let dateDisplay = dateObj.toLocaleString('en-us', {month: 'short'}) + " " + dateObj.getDate();
                daydiv.setAttribute("data-date", dateObj.toDateString());
                daydiv.setAttribute("data-day", dateDisplay);
                daydiv.setAttribute("data-day-number", dateObj.getDate());
                daydiv.setAttribute("day-of-week", thisDay);
                if(document.querySelector("body").classList.contains("page-node-type-calendar")){
                daydiv.innerHTML =  dateObj.getDate();
                }
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

        if(document.querySelector("body").classList.contains("page-node-type-calendar")){
            // make hover events on calendar types
            document.querySelectorAll(".page-node-type-calendar .Column .GC").forEach(function(el){
                el.addEventListener("mouseover", function(e){
                    const col = e.target.closest(".Column");
                    col.classList.add("hoverGC")
                });
                el.addEventListener("mouseout", function(e){
                    const col = e.target.closest(".Column");
                    col.classList.remove("hoverGC")
                });
            });
            document.querySelectorAll(".page-node-type-calendar .Column .HCC").forEach(function(el){
                el.addEventListener("mouseover", function(e){
                    const col = e.target.closest(".Column");
                    col.classList.add("hoverHCC")
                });
                el.addEventListener("mouseout", function(e){
                    const col = e.target.closest(".Column");
                    col.classList.remove("hoverHCC")
                });
            });
            document.querySelectorAll(".page-node-type-calendar .Column .SC").forEach(function(el){
                el.addEventListener("mouseover", function(e){
                    const col = e.target.closest(".Column");
                    col.classList.add("hoverSC")
                });
                el.addEventListener("mouseout", function(e){
                    const col = e.target.closest(".Column");
                    col.classList.remove("hoverSC")
                });
            });
        }
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

        // make all the columns equal in width.
        document.querySelectorAll("#NewCalendarContainer .month").forEach(function(col){
            let columns = col.querySelectorAll(".Column");
            col.classList.add("columns-" + columns.length);

    
            
        });
        document.querySelectorAll("#NewCalendarContainer .Column").forEach(function(col){
            // Wrap first two HCC divs
            const hccDivs = col.querySelectorAll('.HCC');
            if (hccDivs.length >= 2) {
                const wrapperDiv = document.createElement('div');
                wrapperDiv.classList.add('hcc-wrapper');
                const div1 = hccDivs[0];
                const div2 = hccDivs[1];
                div1.parentNode.insertBefore(wrapperDiv, div1);
                wrapperDiv.appendChild(div1);
                wrapperDiv.appendChild(div2);
                
            } 

            // Wrap first two SC divs
            const scDivs = col.querySelectorAll('.SC');
            if (scDivs.length >= 2) {
                const scWrapperDiv = document.createElement('div');
                scWrapperDiv.classList.add('sc-wrapper');
                const scDiv1 = scDivs[0];
                const scDiv2 = scDivs[1];
                scDiv1.parentNode.insertBefore(scWrapperDiv, scDiv1);
                scWrapperDiv.appendChild(scDiv1);
                scWrapperDiv.appendChild(scDiv2);
            }

            // Wrap first three GC divs
            const gcDivs = col.querySelectorAll('.GC');
            if (gcDivs.length >= 3) {
                const gcWrapperDiv = document.createElement('div');
                gcWrapperDiv.classList.add('gc-wrapper');
                const gcDiv1 = gcDivs[0];
                const gcDiv2 = gcDivs[1];
                const gcDiv3 = gcDivs[2];
                gcDiv1.parentNode.insertBefore(gcWrapperDiv, gcDiv1);
                gcWrapperDiv.appendChild(gcDiv1);
                gcWrapperDiv.appendChild(gcDiv2);
                gcWrapperDiv.appendChild(gcDiv3);
            }
        });
        

        if(document.querySelector(".page-node-type-calendar .legend")){
            document.querySelectorAll(".page-node-type-calendar .legend .form-check-input").forEach(function(input){
               // event listener
               input.addEventListener("change", function(e){
                   const body = document.querySelector("body");
                   let id = e.target.id;

                   //console.log("toggling", id, e.target.checked);
                   if(e.target.checked){
                       body.classList.remove(id);
                   } else {
                       body.classList.add(id);
                   }
               });
            });

            document.querySelectorAll(".page-node-type-calendar .legend .form-check-label").forEach(function(input){
                var tooltip = new bootstrap.Tooltip(input)
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
                    //console.log("fetching", fetchURL);
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
        //console.log("getting gregdate")
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
        return;
        // determine if it is the new or old layout
        const body = document.querySelector("body");
        let newLayout = false
        if(body.classList.contains("page-node-type-calendar")){
            newLayout = true;
        } 
        
        const passoverDate = calendar.dates.passover;

        
        const passovverDateParts = passoverDate.split(" ");
        const passoverMonth = passovverDateParts[0];
        const passoverDay = parseInt(passovverDateParts[1]);
        //console.log("passoverMonth", passoverMonth, "passoverDay", passoverDay);
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
        if(firstDate==null){
            if(document.querySelector(".amyear-1")){
                firstDate = secondDate;
            }
        }
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
            //console.log("maybe this single holy day falls on a sabbath", day)

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
            if(document.querySelector(".daygrid[data-day='" + month + " " + day + "']")) {
                document.querySelector(".daygrid[data-day='" + month + " " + day + "']").classList.add("bg-tabernacles");

            } else {
                // maybe its a sabbath
                let sabbathDate = document.querySelector(".Cell[month='" + month + "'][data-day='" + day + "']");
                if(sabbathDate){
                    sabbathDate.querySelector("span").classList.add("bg-tabernacles");
                }
            }


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
            if(document.querySelector(".daygrid[data-day='" + month + " " + day + "']")){
                document.querySelector(".daygrid[data-day='" + month + " " + day + "']").classList.add("bg-unleavenedbread");
            } else {
                //  // maybe it is a sabbath?
                //console.log("maybe the sabbath date", month, day, "is not in the calendar");
                let sabbathDate = document.querySelector(".Cell[month='" + month + "'][data-day='" + day + "']");
                if(sabbathDate){
                    sabbathDate.querySelector("span").classList.add("bg-unleavenedbread");
                }
            }
            
           
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


