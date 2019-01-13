(function($){

	$.UiComponentPanel = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

        base.$el.data('UiComponentPanel', base);
        
        var header = $('> .header', base.el);
        var topLeft = $('> .top > .topLeft', base.el);
        var topRight = $('> .top > .topRight', base.el);
        var main = $('> .main', base.el);
        var content = $('> .content', base.el);

		base.init = function() {
            base.options = o = $.extend({}, $.UiComponentPanel.defaults, options);

            // Add hamburger button to top
            $('<div/>').addClass('buttonSwitchMain').UiComponentButton({
                iconName: o.iconHamburgerName,
                iconVariant: o.iconHamburgerVariant,
                isTransparent: true,
                onClick: function() {
                    base.switchSection('main');
                }
            }).prependTo(topLeft);
            
            $('<div/>').addClass('buttonSwitchMain').UiComponentButton({
                iconName: o.iconHamburgerName,
                iconVariant: o.iconHamburgerVariant,
                isTransparent: true,
                onClick: function() {
                    base.switchSection('main');
                }
            }).appendTo(header);

            base.hookResize();

            base.hookSectionHover('main');

            if (o.isMainOpen && !base.isBelowResponsiveThreshold('smallScreenWidthThreshold'))
                base.openSection('main');

            base.fit();

            setTimeout(function() {
                base.activateAnimations();
            }, 1000);
        }

        base.activateAnimations = function() {
            $(base.el).removeClass('noAnimations');
        }

        base.deactivateAnimations = function() {
            $(base.el).addClass('noAnimations');
        }

        base.hookResize = function() {
            $(window).resize(function() {
                base.fit();
            });
        }

        base.unhookResize = function() {
            $(window).unbind('resize');
        }

        base.hookSectionHover = function(sectionName) {
            $(main).
                on('mouseover', function() {
                    if (!base.isSectionOpen(sectionName)) { 
                        $(main).data('hovered', true);
                        base.openSection(sectionName);
                    }
                }).
                on('mouseout', function() {
                    if ($(main).data('hovered')) {
                        base.closeSection(sectionName);
                        $(main).data('hovered', false);
                    }
                });
        }

        base.unhookSectionHover = function(sectionName) {
            $(main).unbind('mouseover mouseout');
        }

        base.isSectionOpen = function(sectionName) {
            return $(base.el).hasClass(sectionName + 'Open');
        }

        base.switchSection = function(sectionName) {
            if (base.isSectionOpen(sectionName))
                base.closeSection(sectionName);
            else
                base.openSection(sectionName);
        }

        base.openSection = function(sectionName) {
            $(base.el).addClass(sectionName + 'Open');
            base.fit();
        }

        base.closeSection = function(sectionName) {
            $(base.el).removeClass(sectionName + 'Open');
            base.fit();
        }

        base.fit = function() {
            if (base.isBelowResponsiveThreshold('smallScreenWidthThreshold'))
                $(base.el).addClass('small');
            else
                $(base.el).removeClass('small');
            
            if (base.isSectionOpen('main'))
                base.uncollapseBlocksInSection('main');
            else
                base.collapseBlocksInSection('main');
        }

        base.isBelowResponsiveThreshold = function(breakpointName) {
            return $(window).width() < o.responsiveBreakpoints[breakpointName];
        }

        base.getCollapsibleSectionBlocks = function(sectionName) {
            return $('.' + sectionName + ' .UiComponentMenuOptionWithSuboptions, .' + sectionName + ' .UiComponentMenuOption', base.el);
        }

        base.collapseBlocksInSection = function(sectionName) {
            base.getCollapsibleSectionBlocks(sectionName).each(function(idx, block) {
                $(block).addClass('collapsed');
            });
        }

        base.uncollapseBlocksInSection = function(sectionName) {
            base.getCollapsibleSectionBlocks(sectionName).each(function(idx, block) {
                $(block).removeClass('collapsed');
            });
        }
        
		base.init();
	}

	$.UiComponentPanel.defaults = {
        responsiveBreakpoints: {
            smallScreenWidthThreshold: <?= $e->Ui->uiComponents["UiComponentPanel"]->getConfig("responsiveBreakpoints")["smallScreenWidthThreshold"] ?>
        },
        isMainOpen: true,
        iconHamburgerName: 'hamburger',
        iconHamburgerVariant: 'black'
	};

	$.fn.UiComponentPanel = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentPanel');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentPanel(this, options);
			else
				eval('me.'+options+'.apply(me.'+options+', params)');
		});
	}

})(jQuery);