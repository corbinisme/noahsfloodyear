var timeline = {
	master: [],
	associations: {},
	pairs: [],
	assocList: [],
	spacer: 3,
	container: null,
	containerOffset: 0,
	layout: "vert",
	loadedData: null,
	init: function(){
		let test = document.querySelectorAll(".view-id-timeline");
		timeline.container = test[0].querySelector(".view-content");
		
		let elmTop = timeline.container.getBoundingClientRect().top + window.scrollY;
		timeline.containerOffset = elmTop;

		if(test.length){
			timeline.analyzeRows();
			timeline.bindings();
			timeline.addToggleButton();
		}

	},
	addToggleButton:function(){
		let node = document.querySelector(".view-id-timeline").closest(".region-content");

		let buttonEl = document.createElement("button");
		buttonEl.type="button";
		buttonEl.innerHTML = "<i class='fa fa-rotate'></i> Rotate";
		buttonEl.id = "rotateTimeline";
		buttonEl.classList.add("btn");
		buttonEl.classList.add("btn-default");
		buttonEl.onclick = timeline.rotateTimeline;
		//node.prepend(buttonEl)

	},
	extractClassFromRow: function(classes){
		let yearClass ="";
		for (let cssClass of classes) {
			if(cssClass.indexOf("am-")>-1){
				yearClass = cssClass;
			}
		    
		}
		return yearClass;

	},
	rotateTimeline: function(){
		const regionContent = document.querySelector(".view-timeline");
		if(regionContent.classList.contains("horiz")){
			regionContent.classList.remove("horiz");
			timeline.layout = "vert";
		} else {
			regionContent.classList.add("horiz");
			timeline.layout = "horiz";
		}
		timeline.initializeLines();


	},
	bindings: function(){

		document.querySelectorAll('.view-id-timeline .views-row').forEach(item => {
		  item.addEventListener('mouseover', event => {
		    //handle click
		    let row = event.target.closest('.views-row');
		    let am = timeline.extractClassFromRow(row.classList);
		    row.classList.add("hover");
		    let line = "";
		    if(document.querySelector("div[data-am='" + am.replace("am-","") + "']")!=null){
		    	line = document.querySelector("div[data-am='" + am.replace("am-","") + "']")
		    } else {
		    	line = document.querySelector("div.timeline-line[data-related='" + am.replace("am-","") + "']");
		    }
		    if(line){
		    	line.classList.add("hover")
			}



		  });
		   item.addEventListener('mouseout', event => {
		    //handle click
		    let row = event.target.closest('.views-row');
		    let am = timeline.extractClassFromRow(row.classList);
		    row.classList.remove("hover");
		    let line = "";
		    if(document.querySelector("div[data-am='" + am.replace("am-","") + "']")!=null){
		    	line = document.querySelector("div[data-am='" + am.replace("am-","") + "']")
		    } else {
		    	line = document.querySelector("div.timeline-line[data-related='" + am.replace("am-","") + "']");
		    }
		    if(line){
		    	line.classList.remove("hover")
			}

		    
		  });

		  
		})
		document.querySelectorAll('.view-id-timeline .views-field-title a').forEach(item => {
			item.addEventListener('click', function(e){
				e.preventDefault();
				let href = e.target.getAttribute("href");
  
				let nid = e.target.closest(".views-row").querySelector(".views-field-field-am-year").innerText;
  
				if(timeline.loadedData==null){
				  fetch('/timelineRest')
				  .then(response => response.json())
				  .then(data => {
					  
					  timeline.loadedData = {};
					  data.forEach(function(el, idx){
						  
						  let am = el['field_am_year'][0].value;
						  timeline.loadedData[am] = data[idx];
					  });
					  
					  timeline.setModal(timeline.loadedData[nid]);
				  });
  
				  
				} else {
				  
					  
				  timeline.setModal(timeline.loadedData[nid]);
				}
  
  
				
			})
		});

	},
	buildModal: function(){
	
		
		let template = `<div id="timelineModal" class="modal fade" role="dialog">
			<div class="modal-dialog modal-lg">
		
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
				<h4 class="modal-title">Timeline Detail</h4>
				<button type="button" class="close modalClose btn btn-outline-secondary" data-dismiss="modal">&times;</button>
				
				</div>
				<div class="modal-body" style="max-height: 60vh; overflow: auto;">
				<p>Some text in the modal.</p>
				</div>
				<div class="modal-footer">
				<button type="button" class="btn btn-default modalClose" data-dismiss="modal">Close</button>
				</div>
			</div>
		
			</div>
		</div>`;
		let templateNode = document.createElement("div");
		templateNode.innerHTML = template;
		document.querySelector("body").append(templateNode);

		document.querySelectorAll("#timelineModal .modalClose").forEach(function(el){
			el.addEventListener("click", function(e){
				let mod = document.getElementById("timelineModal");
				mod.classList.remove("in")
				mod.classList.remove("show")
			})
		})

	
	},
	sizeModal: function(){
		let modal  = document.getElementById("timelineModal");
		let height = modal.getBoundingClientRect().height;
		let winHeight = window.outerHeight;

		let diff = winHeight-(height);
		


		//modal.style.top = (diff/2)  +  "px";
		

	},
	setModal: function(id){

		const title = id['title'][0].value;
		const body = (id['body'][0]?id['body'][0].processed:"");
		const amyear = id['field_am_year'][0].value;

		if(document.getElementById("timelineModal")==null){
			timeline.buildModal();
		}

		const modal  = document.getElementById("timelineModal");
		const bodyNode = modal.querySelector(".modal-body");
		const buttonBottom = document.createElement("a");
		buttonBottom.classList.add("btn");
		buttonBottom.classList.add("btn-primary");
		buttonBottom.classList.add("text-white");
		buttonBottom.classList.add("m-0");
		buttonBottom.innerHTML = "View Calendar Year";
		buttonBottom.href = "/calendar/am/"+amyear;
		bodyNode.innerHTML = body + buttonBottom.outerHTML;


		modal.classList.add("in")
		modal.classList.add("show")
		//modal.style.top = "0px";
		console.log("modal");
		timeline.sizeModal();

	},
	analyzeRows: function(){
		let base = document.querySelector(".view-id-timeline");
		let rows =document.querySelectorAll(".view-id-timeline .views-row");
		rows.forEach(function(el, idx){
			let yearClass = timeline.extractClassFromRow(el.classList)
		
			// check if it is already in the array,
			// then hide the subsequent year marker

			if(timeline.master.includes(yearClass)){
				el.classList.add("duplicate")
			} else {
				timeline.master.push(yearClass);
			}

			// then get the related year
			// find the difference between the two,
			// and record in timeline.accociations

			// find the biggest range, and draw the outer line
			// then the next biggest, and go 5px in, etc...
				
			    
			let relatedNode = el.querySelector(".views-field-field-related .field-content");
			let relatedAmYear = relatedNode.innerHTML;
			if(relatedAmYear!=""){

				let am =parseInt(yearClass.replace("am-", ""));
				if(relatedAmYear-am > 0){
					timeline.associations[am] = {
						am: am,
						related: relatedAmYear,
						difference: relatedAmYear-am
					}
					timeline.assocList.push(relatedAmYear-am);
				}
			}
			

		});

		timeline.assocList.sort(function(a, b){return b - a});
		timeline.initializeLines()

	},
	initializeLines: function(){
		
		if(document.querySelectorAll(".timeline-line").length){
			document.querySelectorAll(".timeline-line").forEach(function(el){
				el.remove();
			})
		}

		
		let base = document.querySelector(".view-id-timeline");
		let counter = 0;
		timeline.assocList.forEach(function(it){
			for (const property in timeline.associations) {
		  		//timeline.pairs.push(property);
		  		let entry = timeline.associations[property];
		  		if(entry.difference==it){

		  			let thisAM = document.querySelector(".am-"+entry.am);
		  			thisAM.setAttribute("data-related", entry.related);
		  			thisAM.classList.add("connected");

		  			timeline.drawLines(entry.am, entry.related, base, counter);

		  		}
			}
			counter++;
		});
		base.querySelector(".view-content").style.marginLeft = (counter*timeline.spacer)+ "px";

	},
	getElemPosition: function(elem){
		let bodyRect = document.body.getBoundingClientRect();
		let elemRect = elem.getBoundingClientRect();
		let offset   = elemRect.top - bodyRect.top;
		return offset;
	},
	drawLines:function(am, related, node, counter){

		if(timeline.layout == "horiz"){
			
		} else {

		
			let temp = document.createElement("div");
			let amNode = document.querySelector(".am-"+am);
			let relatedNode = document.querySelector(".am-"+related);
			let top = timeline.getElemPosition(amNode);
			let bot = timeline.getElemPosition(relatedNode);
			let height = bot-top;
			let offset = timeline.containerOffset;
			offset -= 20;
			/*
			if(document.getElementById("toolbar-administration")){
				offset += 190;
			}
			*/

			temp.setAttribute("data-am", am);
			temp.classList.add("timeline-line");
			temp.setAttribute("data-related", related);
			temp.style.height =  height + "px";
			temp.style.top = (top-offset) + "px";
			temp.style.left = (counter*timeline.spacer) + "px";
			node.appendChild(temp);
		}
	},
}

window.onload = function(){
	timeline.init();
}
// set window resize event
window.addEventListener('resize', function(event){
	timeline.sizeModal();
});
