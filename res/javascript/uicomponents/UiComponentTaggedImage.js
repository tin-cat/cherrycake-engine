(function($){

	$.TaggedImage = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('TaggedImage', base);

		var containerWidth, containerHeight, containerProportion, imageProportion, containerWidthLastSeenValue, containerHeightLastSeenValue;

		base.init = function() {
			base.options = o = $.extend({}, $.TaggedImage.defaults, options);
			$(base.el).addClass('taggedImage').append('<div class=\'taggedImageContainer\'><img src=\'' + o.imageUrl + '\' class=\'taggedImage\'></div>');
			base.fit();

			// Adds the tags
			if (o.tags)
				for (var i = 0; i < o.tags.length; i++) {
					tag = o.tags[i];
					$('> .taggedImageContainer', base.el).append(
						'<div ' +
							'class=\'tag ' + tag.domId + '\' ' +
							'id=\'' + tag.domId + '\' ' +
							'style=\'left: ' + tag.x + '%; top: ' + tag.y + '%;\' ' +
						'>' +
							tag.html +
						'</div>'
					);

					// Hook the tags
					$('> .taggedImageContainer #' + tag.domId + ' > div', base.el).data('linkedDomSelector', tag.linkedDomSelector);

					$('> .taggedImageContainer #' + tag.domId + ' > div', base.el).on('mouseenter', function() {
						$($(this).data('linkedDomSelector')).addClass(o.cssClassForLinkedElementsOnTagOver);
						$(this).addClass(o.cssClassOnTagOver);
					});

					$('> .taggedImageContainer #' + tag.domId + ' > div', base.el).on('mouseleave', function() {
						$($(this).data('linkedDomSelector')).removeClass(o.cssClassForLinkedElementsOnTagOver);
						$(this).removeClass(o.cssClassOnTagOver);
					});

					// Hook the linked elements
					// Adds the desired linked element to the linkedTo data in the form of an array
					if ($.isArray($(tag.linkedDomSelector).data('linkedTo')))
						$(tag.linkedDomSelector).data('linkedTo', $(tag.linkedDomSelector).data('linkedTo').concat(new Array(tag.domId)));
					else
						$(tag.linkedDomSelector).data('linkedTo', new Array(tag.domId));

					$(tag.linkedDomSelector).on('mouseenter', function() {
						$(this).data('linkedTo').map( function(linkedTo) {
							$('#' + linkedTo + ' > div').addClass(o.cssClassOnTagOver);
						});
					});

					$(tag.linkedDomSelector).on('mouseleave', function() {
						$(this).data('linkedTo').map( function(linkedTo) {
							$('#' + linkedTo + ' > div').removeClass(o.cssClassOnTagOver);
						});
					});
				}

			// Set an automatic examination of container size each N millisecs to trigger base.fit() if it changes
			setInterval(function() {
				if ($(base.el).width() != containerWidthLastSeenValue || $(base.el).height() != containerHeightLastSeenValue)
					base.fit();
			}, o.autoFitOnContainerSizeChangesDelay);
		}

		base.fit = function() {
			containerWidth = $(base.el).width();
			containerHeight = $(base.el).height();

			containerWidthLastSeenValue = containerWidth;
			containerHeightLastSeenValue = containerHeight;

			containerProportion = containerWidth / containerHeight;
			imageProportion = o.imageWidth / o.imageHeight;

			if (o.isDebug) console.log('Container size: ' + containerWidth + 'x' + containerHeight + ' proportion: ' + containerProportion);
			if (o.isDebug) console.log('Image size: ' + o.imageWidth + 'x' + o.imageHeight + ' proportion: ' + imageProportion);

			var finalImageSize = base.calculateFinalImageSize();

			$('.taggedImageContainer', base.el).css('width', finalImageSize.width);
			$('.taggedImageContainer', base.el).css('height', finalImageSize.height);

			$('.taggedImage', base.el).css('width', finalImageSize.width);
			$('.taggedImage', base.el).css('height', finalImageSize.height);

			var finalTaggedImageContainerPosition = base.calculateFinalTaggedImageContainerPosition(finalImageSize);

			// Center the taggedImageContainer
			$('.taggedImageContainer', base.el).css('top', finalTaggedImageContainerPosition.y);
			$('.taggedImageContainer', base.el).css('left', finalTaggedImageContainerPosition.x);
		}

		base.calculateFinalImageSize = function(scalingMethod) {
			var finalImageWidth, finalImageHeight;

			if (!scalingMethod)
				scalingMethod = o.scaling;

			switch (scalingMethod) {
				case 'fit':

					if (o.isDebug) console.log('Using scaling method: fit');

					if (imageProportion < containerProportion) {
						finalImageHeight = containerHeight;
						finalImageWidth = Math.ceil( (o.imageWidth * containerHeight) / o.imageHeight);
					}
					else {
						finalImageWidth = containerWidth;
						finalImageHeight = Math.ceil( (o.imageHeight * containerWidth) / o.imageWidth);
					}
					break;

				case 'cover':

					if (o.isDebug) console.log('Using scaling method: cover');

					if (imageProportion < containerProportion) {
						finalImageWidth = containerWidth;
						finalImageHeight = Math.ceil( (o.imageHeight * containerWidth) / o.imageWidth);
					}
					else {
						finalImageHeight = containerHeight;
						finalImageWidth = Math.ceil( (o.imageWidth * containerHeight) / o.imageHeight);
					}

					if (
						o.autoSetScalingToFitHiddenPixelsThreshold
						&&
						(
							finalImageWidth - containerWidth > o.autoSetScalingToFitHiddenPixelsThreshold
							||
							finalImageHeight - containerHeight > o.autoSetScalingToFitHiddenPixelsThreshold
						)
					)
						return base.calculateFinalImageSize('fit');

					break;
			}

			if (o.isDebug) console.log('Final image size: ' + finalImageWidth + 'x' + finalImageHeight);

			return {width: finalImageWidth, height: finalImageHeight};
		}

		base.calculateFinalTaggedImageContainerPosition = function(imageSize) {
			var x, y;

			x = Math.ceil((containerWidth / 2) - (imageSize.width / 2));
			y = Math.ceil((containerHeight / 2) - (imageSize.height / 2));

			if (o.isDebug) console.log('Final image container position: x:' + x + ' y:' + y);

			return {x: x, y: y};
		}

		base.init();
	}

	$.TaggedImage.defaults = {
		isDebug: false, // Whether to show debug information in the console or not
		imageUrl: false, // Must be set
		imageWidth: false, // Must be set
		imageHeight: false, // Must be set
		scaling: 'cover', // The image scaling method
		autoSetScalingToFitHiddenPixelsThreshold: false, // When this number of pixels or more from the image is to be hidden from view, the scaling is set automatically to "fit"
		autoFitOnContainerSizeChangesDelay: 300, // Check the container size each this millisecs. If the size has changed, make the image fit again
		cssClassOnTagOver: 'visible', // The Css class name that will be added/removed to the tag element when hovering the tag
		cssClassForLinkedElementsOnTagOver: 'highlight' // The Css class name that will be added/removed to the linked elements when hovering the tag
	};

	$.fn.TaggedImage = function(options) {
		return this.each(function(){
			var me = $(this).data('TaggedImage');
			if ((typeof(options)).match('object|undefined'))
				new $.TaggedImage(this, options);
		});
	}

})(jQuery);