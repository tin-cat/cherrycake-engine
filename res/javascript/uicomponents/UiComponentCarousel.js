(function($){

	$.UiComponentCarousel = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentCarousel', base);

		base.width = 0;
		base.currentItemIndex = 0;
		base.numberOfItems = 0;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentCarousel.defaults, options);

			base.width = $(base.el).width();

			base.currentItemIndex = o.defaultItem;
			base.numberOfItems = $('> .items > .item', base.el).length;

			// Hide all except default
			$('> .items > .item', base.el).each(function(index) {
				if(index != base.currentItemIndex)
					$(this).css('opacity', 0);
			});

			// Add navigation bullets
			$(base.el).prepend('<div class="navigationBullets"></div>');

			for(var itemIndex = 0; itemIndex < base.numberOfItems; itemIndex ++)
				$('> .navigationBullets', base.el).append('<a class="item ' + itemIndex.toString() + (itemIndex == base.currentItemIndex ? ' selected' : '') + '" data-itemindex="' + itemIndex.toString() + '"></a>');

			$('> .navigationBullets > a.item', base.el).on('click', function() {
				base.goToItem($(this).data('itemindex'));
			});

			base.fit();
			base.goToItem(base.currentItemIndex);
		}

		base.fit = function() {
			$('> .items > .item', base.el).each(function(index) {
				$(this).css('left', index * base.width);
			});

			$('> .navigationBullets', base.el).css('margin-left', Math.round((base.numberOfItems * 18) / 2)*-1);
		}

		base.focus = function() {
			base.hookKeys();
		}

		base.blur = function() {
			base.unHookKeys();
		}

		base.hookKeys = function() {
			$(window).keyup(function(e){
				if(e.keyCode === 37) {
					base.goToPrevious();
					e.preventDefault();
				}
				else
				if(e.keyCode === 39) {
					base.goToNext();
					e.preventDefault();
				}
			});
		}

		base.unHookKeys = function() {
			$(window).unbind('keyup');
		}

		base.goToNext = function() {
			if(base.currentItemIndex == base.numberOfItems-1)
				return false;

			base.goToItem(base.currentItemIndex+1);
		}

		base.goToPrevious = function() {
			if(base.currentItemIndex == 0)
				return false;

			base.goToItem(base.currentItemIndex-1);
		}

		base.goToItem = function(itemIndex) {
			if(itemIndex == base.currentItemIndex)
				return;

			$('> .items > .item', base.el).each(function(index) {
				if(index == itemIndex)
					$(this).animate(
						{
							opacity: 1
						},
						{
							duration: o.itemChangeDuration,
							queue: false
						}
					);
				else
					$(this).animate(
						{
							opacity: 0
						},
						{
							duration: o.itemChangeDuration,
							queue: false
						}
					);
			});

			$('> .items', base.el).animate(
				{
					left: itemIndex * base.width * -1
				},
				{
					duration: o.itemChangeDuration,
					queue: false
				}
			);

			// Update navigationBullets
			$('> .navigationBullets > a.item', base.el).removeClass('selected');
			$('> .navigationBullets > a.item.'+itemIndex, base.el).addClass('selected');

			base.currentItemIndex = itemIndex;
		}

		base.init();
	}

	$.UiComponentCarousel.defaults = {
		defaultItem: 0,
		itemChangeDuration: 500
	};

	$.fn.UiComponentCarousel = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentCarousel');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentCarousel(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);