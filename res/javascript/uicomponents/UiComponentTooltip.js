(function($){

	$.UiComponentTooltip = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentTooltip', base);

		var isOpen = false;
		var openTimeout = false;
		var closeTimeout = false;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentTooltip.defaults, options);

			// Remove older tooltip if there is one
			base.remove();

			// Set base element position to relative
			$(base.el).css('position', 'relative');

			$(base.el).addClass('UiComponentTooltipContainer');

			// Add tooltip html elements
			$(base.el).append('<div class="UiComponentTooltip"><div class="tooltip"><div class="content"></div><div class="arrow"></div></div></div>');

			if (o.offsetX || o.offsetY) {
				$('> .UiComponentTooltip', base.el).css({'transform': 'translate(' + o.offsetX +'px, ' + o.offsetY + 'px)'});
				$('> .UiComponentTooltip', base.el).css({'-webkit-transform': 'translate(' + o.offsetX +'px, ' + o.offsetY + 'px)'});
			}

			// Apply styles
			$('> .UiComponentTooltip', base.el).addClass(o.position);

			if (o.isOpenOnInit)
				base.open();

			switch (o.openMethod) {
				case 'onHover':
					$(base.el).
						on('mouseenter', function() {
							base.open();
						}).
						on('mouseleave', function() {
							base.close();
						});
					break;

				case 'onClick':
					$(base.el).
						on('click', function() {
							base.switch();
						});
					break;
			}
		}

		base.setStyle = function(style) {
			if (!style)
				return;

			// Removes all classes starting with "style"
			if($(base.el).attr('class')) {
				UiComponentTooltipClasses = $(base.el).attr('class').split(' ');
				for(i=0; i<UiComponentTooltipClasses.length; i++) {
					if(UiComponentTooltipClasses[i].substr(0, 5) == 'style')
						$(base.el).removeClass(UiComponentTooltipClasses[i]);
				};
			}

			$('> .UiComponentTooltip', base.el).addClass(style);
		}

		base.setOnClick = function(onclick) {
			$('> .UiComponentTooltip', base.el).addClass('clickable');
			$('> .UiComponentTooltip', base.el).on('click', onclick);
		}

		/**
		 * content have the following forms:
		 * * A string: The passed HTML will be set inside the tooltip, it is up to the HTML to format the contents, add paddings, etc. Commonly receives the results of UiComponentTooltip::buildContentItem
		 * * An array with one of this keys:
		 * * * text: The content of the tooltip will contain this text in a formatted fashion, used to pass simple strings to be shown on the tooltip.
		 */
		base.setContent = function(content, style) {
			if (typeof content === 'object' && 'contentItems' in content)
				content = content.contentItems;
			else
				content = '<div class="content">' + content + '</div>';

			if (
				(o.isTapToPopupOnSmallScreens && $(window).width() < o.maxWindowWidthToConsiderSmallScreen)
				||
				(o.isTapToPopupWhenMoreThanLines && (content.split("<br>").length) > o.isTapToPopupWhenMoreThanLines)
			) {
				base.setOnClick(function() {
					$('#UiComponentNotice').UiComponentNotice('open', [content, style]);
				});
				$('> .UiComponentTooltip > .tooltip > .content', base.el).html('<div class="simple"><div class="UiComponentIcon more white"></div></div>');
			}
			else {
				$('> .UiComponentTooltip > .tooltip > .content', base.el).html(content);
			}
		}

		base.clearContent = function() {
			$('> .UiComponentTooltip > .tooltip > .content', base.el).html('');
		}

		base.isOpen = function() {
			return isOpen;
		}

		base.open = function(content, style) {
			if (o.isCloseDelay)
				clearTimeout(closeTimeout);

			if (o.isOpenDelay)
				openTimeout = setTimeout(function() {
					base.doOpen(content, style);
				}, o.openDelay);
			else
				base.doOpen(content, style);
		}

		base.doOpen = function(content, style) {
			$('.UiComponentTooltipContainer').UiComponentTooltip('doCloseBecauseOtherOpened');

			if (!style)
				style = o.style;

			base.setStyle(style);
			if (content)
				base.setContent(content, style);
			else
			if (o.content)
				base.setContent(o.content, style);
			else
			if (o.ajaxUrl) {
				if (!o.ajaxSetup)
					o.ajaxSetup = Array;
				o.ajaxSetup.success = function(data) {
					if (data.tooltipStyle)
						base.setStyle(data.tooltipStyle);
					base.setContent(data.tooltipContent, style);
				}
				ajaxQuery(o.ajaxUrl, o.ajaxSetup);
			}
			$('> .UiComponentTooltip', base.el).show();

			isOpen = true;
		}

		base.switch = function() {
			if (base.isOpen())
				base.close();
			else
				base.open();
		}

		base.close = function() {
			if (o.isOpenDelay)
				clearTimeout(openTimeout);

			if (o.isCloseDelay)
				closeTimeout = setTimeout(function() {
					base.doClose();
				}, o.closeDelay);
			else
				base.doClose();
		}

		base.doClose = function() {
			if (o.isOpenDelay)
				clearTimeout(openTimeout);
			$('> .UiComponentTooltip', base.el).hide();
			isOpen = false;
		}

		base.doCloseBecauseOtherOpened = function() {
			if (o.isCloseWhenOthersOpen)
				base.doClose();
		}

		base.remove = function() {
			$('> .UiComponentTootlip', base.el).remove();
		}

		base.init();
	}

	$.UiComponentTooltip.defaults = {
		style: false,
		position: '<?= $e->Ui->uiComponents["UiComponentTooltip"]->getConfig("defaultPosition") ?>',
		isOpenOnInit: false,
		isCloseWhenOthersOpen: true,
		openMethod: false,
		content: false,
		ajaxUrl: false,
		ajaxSetup: false,
		isOpenDelay: <?= ($e->Ui->uiComponents["UiComponentTooltip"]->getConfig("defaultIsOpenDelay") ? "true" : "false") ?>,
		openDelay: <?= $e->Ui->uiComponents["UiComponentTooltip"]->getConfig("defaultOpenDelay") ?>,
		isCloseDelay: <?= ($e->Ui->uiComponents["UiComponentTooltip"]->getConfig("defaultIsCloseDelay") ? "true" : "false") ?>,
		closeDelay: <?= $e->Ui->uiComponents["UiComponentTooltip"]->getConfig("defaultCloseDelay") ?>,
		isTapToPopupOnSmallScreens: false,
		isTapToPopupWhenMoreThanLines: 3,
		maxWindowWidthToConsiderSmallScreen: 640,
		offsetX: 0,
		offsetY: 0
	};

	$.fn.UiComponentTooltip = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentTooltip');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentTooltip(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);