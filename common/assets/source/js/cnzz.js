(function(){
	function hidden(){
		var element = document.querySelector('[title=站长统计]');
		if(element){
			element.style.display = 'none';
			return true;
		}
		return false;
	}

	if(!hidden()){
		var interval = setInterval(function(){
			if(hidden()){
				clearInterval(interval);
			}
		}, 50);

		setTimeout(function(){
			clearInterval(interval);
		}, 5000);
	}
})();