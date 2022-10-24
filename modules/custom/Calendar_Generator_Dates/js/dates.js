var dates = {

    init: function(){
        console.log("dates!");
        dates.setupSignificantDates();
    },
    setupSignificantDates: function(){
        document.querySelectorAll(".SignificantDateRow").forEach(function(row){
            row.querySelector(".views-field-body").classList.add("hidden");
            row.querySelector(".views-field-title a").addEventListener("click", function(e){
                e.preventDefault();
                let href = e.target.href;
                let node = e.target.closest(".SignificantDateRow").querySelector(".views-field-body");
                if(node.classList.contains("hidden")){
                    node.classList.remove("hidden")
                    e.target.classList.add("showing");
                    e.target.closest(".SignificantDateRow").classList.add("showing");
                } else {
                    node.classList.add("hidden")
                    e.target.classList.remove("showing");
                    e.target.closest(".SignificantDateRow").classList.remove("showing");
                }

            })
        })
    }
}
window.onload = function(){
    dates.init();
}
