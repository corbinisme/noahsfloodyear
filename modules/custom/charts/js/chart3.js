var bcpcharts = {
    data: null,
    init: function(){
        fetch("/biblical-research/chart3")
        .then(resp=>resp.json())
        .then(data=>{
            console.log(data);
            this.data = data.data;
            this.build();
        })
    },
    build: function(){
        this.data.forEach(function(el){
            console.log(el)
        });

        
    }
}

bcpcharts.init();