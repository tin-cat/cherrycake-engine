(function($){

	$.UiComponentSlideShow = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentSlideShow', base);

		var windowHeight;
		var windowWidth;
		var lastScrollDirection = false;
		var resizeDetectTimeout = false;
		var scrollDirectionTimeout = false;
		var currentSlideIndex = false;
		var lastSlideIndex = false;
		var numberOfSlides = false;
		var numberOfBackgrounds = false;

		var backgroundFinalHeight = false;
		var backgroundHiddenFragmentHeight = false;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentSlideShow.defaults, options);

			if(o.isNavigationViaScroll)
				base.hookScroll();
			base.currentSlideIndex = 0;
			base.lastSlideIndex = 0;
			base.numberOfSlides = $('> .slides > .slide', base.el).length;

			// Set body to overflow: hidden to avoid weird scrollings
			$('body').css('overflow', 'hidden');

			// Set background color
			if(o.backgroundColor)
				$(base.el).css('background-color', o.backgroundColor);

			// Add backgrounds
			if(o.backgroundImages.length) {
				base.numberOfBackgrounds = o.backgroundImages.length;
				for(var backgroundIndex = 0; backgroundIndex < base.numberOfBackgrounds; backgroundIndex++) {
					$(base.el).append('<img class="background ' + backgroundIndex + '" src="' + o.backgroundImages[backgroundIndex] + '" />');
					if(backgroundIndex > 0)
						$('> .background.'+backgroundIndex, base.el).css('opacity', 0);
				}
			}

			// Add credits
			if(o.slideCredits.length)
				$(base.el).prepend('<div class="credits"></div>');

			// Add navigation bullets
			if(o.isNavigationBullets && base.numberOfSlides > 1) {
				$(base.el).prepend('<div class="navigationBullets"></div>');

				for(var slideIndex = 0; slideIndex < base.numberOfSlides; slideIndex ++)
					$('> .navigationBullets', base.el).append('<a class="item ' + slideIndex.toString() + (slideIndex == base.currentSlideIndex ? ' selected' : '') + '" data-slideindex="' + slideIndex.toString() + '"></a>');

				$('> .navigationBullets > a.item', base.el).on('click', function() {
					base.goToSlide($(this).data('slideindex'));
				});
			}

			// Add navigation arrows
			if(o.isNavigationArrows && base.numberOfSlides) {
				$(base.el).prepend('<div class="navigationArrow previous"></div><div class="navigationArrow next"></div>');

				$('> .navigationArrow.previous', base.el).on('click', function() {
					base.slideUp();
				});

				$('> .navigationArrow.next', base.el).on('click', function() {
					base.slideDown();
				});
			}

			// Hook keys
			if(o.isNavigationKeys) {
				$(document).keyup(function(e){
					if(e.keyCode === 38 || e.keyCode === 33) {
						base.slideUp();
						e.preventDefault();
					}
					else
					if(e.keyCode === 40 || e.keyCode == 34) {
						base.slideDown();
						e.preventDefault();
					}
					else
					if(e.keyCode == 36) {
						base.goToSlide(0);
						e.preventDefault();
					}
					else
					if(e.keyCode == 35) {
						base.goToSlide(base.numberOfSlides-1);
						e.preventDefault();
					}
				});
			}

			// Hook gestures if available
			if(o.isNavigationViaMobileGestures && jQuery().TouchWipe) {
				$(base.el).TouchWipe({
					wipeUp: function() {
						base.slideUp();
					},
					wipeDown: function() {
						base.slideDown();
					}
				});
			}

			// Hook window resize
			$(window).resize(function() {
				clearTimeout(base.resizeDetectTimeout);
				base.resizeDetectTimeout = setTimeout(function() {
					base.fit();
				}, o.slideChangeDuration+100);
			});

			// Add carousels
			if(jQuery().UiComponentCarousel)
				$('> .slides > .slide .UiComponentCarousel', base.el).UiComponentCarousel();

			if(o.isAutoGoToLocationByUrlHashOnInit) {
				var slideIndexByHash = base.getSlideIndexByName(window.location.hash.substr(1));
				if(slideIndexByHash)
					base.currentSlideIndex = slideIndexByHash;
			}

			base.fit();
		}

		base.fit = function() {
			$('> .slides', base.el).stop();
			$('> .background', base.el).stop();

			base.windowWidth = $(window).width();
			base.windowHeight = $(window).height();

			$('> .slides > .slide', base.el).each(function(index) {
				$(this).css('height', base.windowHeight);
				$(this).css('top', index * base.windowHeight);
			});

			// Fit background
			if(o.backgroundImages.length) {
				$('> .background', base.el).css('width', base.windowWidth);
				base.backgroundFinalHeight = Math.round((o.backgroundHeight * base.windowWidth) / o.backgroundWidth);

				// Portrait
				if(base.backgroundFinalHeight < base.windowHeight) {
					base.backgroundFinalHeight = base.windowHeight;
					$('> .background', base.el).css('height', base.windowHeight);
					base.backgroundFinalWidth = Math.round((o.backgroundWidth * base.windowHeight) / o.backgroundHeight);
					$('> .background', base.el).css('width', base.backgroundFinalWidth);

					$('> .background', base.el).css('left', ((base.windowWidth - base.backgroundFinalWidth) / 2));
				}
				else
					$('> .background', base.el).css('left', 0);

				$('> .background', base.el).css('height', base.backgroundFinalHeight);

				base.backgroundHiddenFragmentHeight = base.backgroundFinalHeight - base.windowHeight;
			}

			base.goToSlide(base.currentSlideIndex);
		}

		base.hookScroll = function() {
			var mousewheelevt = (/Firefox/i.test(navigator.userAgent)) ? "DOMMouseScroll" : "mousewheel" //FF doesn't recognize mousewheel as of FF3.x
			$(base.el).bind(mousewheelevt, function(e){

				var evt = window.event || e //equalize event object
				evt = evt.originalEvent ? evt.originalEvent : evt; //convert to originalEvent if possible
				var delta = evt.detail ? evt.detail*(-40) : evt.wheelDelta //check for detail first, because it is used by Opera and FF

				if(delta > o.scrollDeltaPixelSensitivity) {
					if(base.lastScrollDirection != 'u') {
						base.lastScrollDirection = 'u';

						clearTimeout(base.scrollDirectionTimeout);
						base.scrollDirectionTimeout = setTimeout(function(){
							base.lastScrollDirection = false;
						}, o.scrollDetectionTimeout);

						base.slideUp();
					}
				}
				else
				if(delta < (o.scrollDeltaPixelSensitivity * -1)) {
					if(base.lastScrollDirection != 'd') {
						base.lastScrollDirection = 'd';
						clearTimeout(base.scrollDirectionTimeout);
						base.scrollDirectionTimeout = setTimeout(function(){
							base.lastScrollDirection = false;
						}, o.scrollDetectionTimeout);

						base.slideDown();
					}
				}
			});
		}

		base.slideUp = function() {
			base.goToSlide(base.currentSlideIndex-1);
		}

		base.slideDown = function() {
			base.goToSlide(base.currentSlideIndex+1);
		}

		base.getSlideIndexByName = function(slideName) {
			var slideIndex = false;
			$('> .slides > .slide', base.el).each(function(index) {
				if ($(this).data('name') == slideName)
					slideIndex = index;
			});
			return slideIndex;
		}

		base.goToSlideName = function(slideName) {
			var slideIndex = base.getSlideIndexByName(slideName);
			base.goToSlide(slideIndex);
		}

		base.goToSlide = function(slideIndex) {
			if(slideIndex < 0 || slideIndex > (base.numberOfSlides-1))
				return false;

			base.lastSlideIndex = base.currentSlideIndex;
			base.currentSlideIndex = slideIndex;

			// Slides destination offset
			var destinationOffset = slideIndex * -1 * base.windowHeight;

			$('> .slides', base.el).animate(
				{
					top: destinationOffset
				},
				{
					duration: o.slideChangeDuration,
					queue: false
				}
			);

			// Background destination offset
			var backgroundDestinationOffset =
				((destinationOffset * -1)  * base.backgroundHiddenFragmentHeight)
				/
				((base.numberOfSlides - 1) * base.windowHeight);

			if(o.backgroundImages.length) {
				$('> .background', base.el).animate(
					{
						top: backgroundDestinationOffset * -1
					},
					{
						duration: o.slideChangeDuration,
						queue: false,
						step: function() {
							$(this).css({
								"top": this.top
							});
						}
					}
				);

				// Background transition
				var lastSlideBackgroundIndex = $('> .slides > .slide:eq(' + base.lastSlideIndex + ')', base.el).data('backgroundindex');
				var currentSlideBackgroundIndex = $('> .slides > .slide:eq(' + base.currentSlideIndex + ')', base.el).data('backgroundindex');

				if(lastSlideBackgroundIndex != currentSlideBackgroundIndex) {

					// If the new background is over the old
					if(currentSlideBackgroundIndex > lastSlideBackgroundIndex) {

						// Reveal the new background
						$('> .background.' + currentSlideBackgroundIndex, base.el).animate(
							{
								opacity: 1
							},
							{
								duration: o.backgroundFadeDuration,
								queue: false,
								complete: function() {
									var currentSlideBackgroundIndex = $('> .slides > .slide:eq(' + base.currentSlideIndex + ')', base.el).data('backgroundindex'); // Recalculate this here again because it's possible that another animation had been done while doing this one
									$('> .background:not(.' + currentSlideBackgroundIndex + ')', base.el).css('opacity', 0);
								}
							}
						);

					}
					// If the new background is below the old
					else {

						// Reveal the new background behind
						$('> .background.' + currentSlideBackgroundIndex, base.el).css('opacity', 1);
						// Hide the old background
						$('> .background.' + lastSlideBackgroundIndex, base.el).animate(
							{
								opacity: 0
							},
							{
								duration: o.backgroundFadeDuration,
								queue: false
							}
						);

					}

				}
			}

			// Update navigationBullets
			if(o.isNavigationBullets) {
				$('> .navigationBullets > a.item', base.el).removeClass('selected');
				$('> .navigationBullets > a.item.'+slideIndex, base.el).addClass('selected');
			}

			// Update navigationArrows
			if(o.isNavigationArrows) {
				if(base.currentSlideIndex == 0)
					$('> .navigationArrow.previous', base.el).fadeOut();
				else
					$('> .navigationArrow.previous', base.el).fadeIn();

				if(base.currentSlideIndex == base.numberOfSlides-1)
					$('> .navigationArrow.next', base.el).fadeOut();
				else
					$('> .navigationArrow.next', base.el).fadeIn();
			}

			// Focus/Blur any Carousel found on this slide
			if(jQuery().UiComponentCarousel)
				$('> .slides > .slide', base.el).each(function(index) {
					if(index == base.currentSlideIndex)
						$('.UiComponentCarousel', this).UiComponentCarousel('focus');
					else
						$('.UiComponentCarousel', this).UiComponentCarousel('blur');
				});

			// Show corresponding credits
			if(o.slideCredits.length) {
				if(o.slideCredits[base.currentSlideIndex] != '')
					$('> .credits', base.el).html(o.slideCredits[base.currentSlideIndex]).fadeIn();
				else
					$('> .credits', base.el).fadeOut();
			}
		}

		base.init();
	}

	$.UiComponentSlideShow.defaults = {
		backgroundColor: false,
		backgroundImages: false,
		slideCredits: false,
		scrollDeltaPixelSensitivity: 50,
		scrollDetectionTimeout: 1000,
		slideChangeDuration: 500,
		backgroundFadeDuration: 500,
		backgroundWidth: false,
		backgroundHeight: false,
		isNavigationBullets: true,
		isNavigationArrows: true,
		isNavigationKeys: true,
		isNavigationViaScroll: true,
		isNavigationViaMobileGestures: true,
		isAutoGoToLocationByUrlHashOnInit: true
	};

	$.fn.UiComponentSlideShow = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentSlideShow');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentSlideShow(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);