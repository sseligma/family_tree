$( document ).ready(function() {
  if (root != '') {
    dTree.init(treeData, {
		  target: "#graph",
		  debug: true,
		  height: 2000,
		  width: 3500,
		  nodeWidth: 70,
		  callbacks: {
		    nodeClick: function(name, extra) {
		      console.log(name);
		    }
		  }
	});  
  }
	
});
