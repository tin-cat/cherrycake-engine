function animationEffectShake(element) {
	var times = 3;
	var distance = 5;
	var interval = 70;
	for (var i=0; i<(times+1); i++)
		$(element).animate({left: ((i%2==0 ? distance : distance*-1))}, interval);
	$(element).animate({left: 0},interval);
}