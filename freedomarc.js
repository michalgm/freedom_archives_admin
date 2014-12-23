$(window).load(function() {
	 $("#jquery_jplayer_1").jPlayer({
		ready: function (event) {
			//$(this).jPlayer("setMedia", {
			//	mp3: "http://www.freedomarchives.org/prisons/Attica_FA01.mp3"
			//});
		},
        swfPath: "js/jplayer/",
        supplied: "mp3",
		preload: 'auto'
	});
	$('#modal').on($.modal.BEFORE_CLOSE, function(event, modal) {
		$("#jquery_jplayer_1").jPlayer("clearMedia"); 
		$('#modal_vimeo').empty();
	});
	var menu = $( "#collections_nav_menu" ).menu({ icons: {submenu:''}, position: { my: "right top", at: "left top" } });
	//var submenus = $( "#collections_menu .submenu" ).menu({ icons: {submenu:''}, position: { my: "right top", at: "left top" } });
	$('#collections_menu').mouseenter(function () { $('#collections_nav_menu').show(); });
	$('#collections_menu').mouseleave(function () { $('#collections_nav_menu').hide(); });
	$(menu).mouseleave(function () {
		menu.menu('collapseAll');
	});
	//$("#wordcloud").jQCloud(word_list, {width: 200, height: 200, shape: 'rectangular', delayedMode: 1});
	
	// Call fitVid before FlexSlider initializes, so the proper initial height can be retrieved.
	$(".flexslider")
	//.fitVids()
	.flexslider({
	  animation: "slide",
	  //useCSS: false,
	  //animationLoop: false,
	  //slideshow: false,
	  smoothHeight: true,
	  controlNav: 'thumbnails'
	 // before: function(slider){
	//	$f(player).api('pause');
	  //}
	});
	if ($('#wordcloud')) { 
		var x = 0;
		while($('#wordcloud').height() > 200 && x < 40) { 
			var size = parseFloat($('#wordcloud').css('font-size'));
			size *=(1-x/10);
			$('#wordcloud').css('font-size', size+'px');
			size = parseFloat($('#wordcloud').css('font-size'));
			//$('#wordcloud').css('line-height', ((size*=.98)*3)+'px');
			//$('#wordcloud').css('line-height', '1em');
			x++;
		}
	}
	$('.doc_description').expander({
		slicePoint: 400, 
		detailClass:'expanded', 
		expandText: 'see more<span>&laquo;</span>',
		userCollapseText: 'see less<span>&raquo;</span>',
		afterExpand: function(d) { 
			$(this).children('.expanded').css('display', 'inline'); 
		}
	});
});

function showDoc(title, media_type, url) {
	media_type = media_type.toLowerCase();
	if (media_type == 'mp3' || media_type == 'video' || media_type == 'image') { 
		var modal = $('#modal');
		$('#modal_title').html(title);
		$('#modal>div').hide();
		if (media_type == 'mp3') { 
			//$('#modal_player').prepend($('#jquery_jplayer_1'));
			$('#modal_player').show();
			$('#jquery_jplayer_1').jPlayer("setMedia", { mp3: url} );
			$("#jquery_jplayer_1").jPlayer("play");
		} else if (media_type == 'video') { 
			var vid = $.trim(url.split('/').reverse()[0]);
			$('#modal_vimeo').html('<iframe id="vimeo" src="http://player.vimeo.com/video/'+vid+'?title=0&amp;byline=0&amp;portrait=0&amp;api=1&autoplay=1" width="400" height="300" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>');
			$('#modal_vimeo').show();
		} else if (media_type == 'image') { 
			$('#modal_content').html("<img src='"+url+"'/>");
			$('#modal_content img').on('load', $.modal.resize);
			$('#modal_content').show();

		}
		modal.modal();
	} else {
		window.open(url, '_blank');
	}
}

function showMoreFilters(filter) {
	filter = '.'+filter;
	for (var x=0; x <5; x++) { 
		$(filter+' li.hidden:first').show();
		$(filter+' li.hidden:first').removeClass('hidden');
	}
	if(! $(filter+' li.hidden')[0] ) { 
		$(filter+' li.more_filters').remove();
	}
	return false;
}

function showHelp() {
	var height = $(window).height();
	$('#help_contents').css('maxHeight', height*.7);
	$('#help_info').modal();
}
