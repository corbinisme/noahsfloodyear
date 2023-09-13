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
        calendar.placePassover();
        calendar.binding();
        calendar.expandContent();
        
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
    makeDateString: function(date){
        let dateString = date;
        dateString = dateString.substring(dateString.indexOf(",")+1, dateString.length);
        dateString = dateString.substring(0, dateString.indexOf(",")).trim()

        console.log(dateString)
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
        
        console.log(calendar.dates)

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

        const nisanMonth = document.getElementById("Nisan5").closest(".month");
        const nisanMonthCount = parseInt(nisanMonth.getAttribute("data-month-count"));

        let passoverMonthNode =null;
        document.querySelectorAll(".month." + passoverMonth).forEach(function(mo){
            if(parseInt(mo.getAttribute("data-month-count")) >= nisanMonthCount){
                passoverMonthNode = mo;
            }
        })
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
            if(passoverDay > date){
                firstDate = date;
            } else if(passoverDay < date){
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
        pentecostNode.classList.add("pentecostcount_7");
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
            calendar.placePentecostWeekCounts();
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


