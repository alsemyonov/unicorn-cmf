Event.observe(window, 'load', function() {
	var previews = $$('.preview');
	alert(previews.toJSON());
	previews.each(function(preview){
		Event.observe(preview, 'click', function(event) {
			var elt = Event.element(event);
			Effect.toggle(elt);
		});
	});
});