/**
 * PIXBUILDER
 * By PixFort
 * Copyright 2017
 * www.pixfort.com
 * 
 * Customization by steve.scargall@intel.com
 */

 $( window ).on("load", function() {		

	// add the animation to the modal
	$( ".modal" ).each(function(index) {
        var self = $(this);
		$(this).on('show.bs.modal', function (e) {
			$(this).addClass('in');
        	$(this).find('.modal-dialog').velocity('transition.fadeIn');
       		$(this).show();	    	      
		}); 
		$(this).on('hide.bs.modal', function (e) {
			$(this).find('.modal-dialog').velocity('transition.fadeOut');
			$(this).removeClass('in');
			var self = this;
			$(this).delay(500).queue(function() {
		     	$(self).hide();	    	
				$(this).dequeue();
		  	});
			$('body').removeClass('modal-open');
            $('iframe').each(function(){
                var leg=$(this).attr("src");
                $(this).attr("src",leg);
            });
			e.stopPropagation();
	    	e.preventDefault();
	    	return false;
		});
        if(self.hasClass('pix_popup')&&self.attr('data-wait')&&self.attr('data-wait')!=''){
            var wait_time = self.attr('data-wait')*1000;
            setTimeout(
                function(){
                    self.modal('show');
                }, wait_time);
        }
	});

	// Hide this section until the vistor registers
	$(document).ready(function() {
		$("#signup_result").hide();
	});

	/*
	 * Form Submission Handler
	 */
	$("#FORM-MKA-18980").submit(function( event ) {
        // Stop form from submitting normally
        event.preventDefault();
        var values = {};
        var temp_str = "";
        var theform = this;
        var proceed = true;
        var is_confirm = false;
        var confirm_pop = "";
        var is_redirect = false;
        var redirect_link = "";
        var have_type = false;
        var the_type = "";
        var the_list = "";
        var have_list = false;
        var action_url = "";

        $('.alert').slideUp();
        
        if($(theform).attr('pix-popup')){
            confirm_pop = $(theform).attr('pix-popup');
            is_confirm = true;
        }
        if($(theform).attr('pix-redirect')){
            redirect_link = $(theform).attr('pix-redirect');
            is_redirect = true;
        }
        if($(theform).attr('pix-form-type')){
            if(($(theform).attr('pix-form-type')!='') && ($(theform).attr('pix-form-type')!='#' )){
                the_type = $(theform).attr('pix-form-type');
                have_type = true;    
            }
        }
        if($(theform).attr('pix-list-id')){
            if($(theform).attr('pix-list-id')!=''){
                the_list = $(theform).attr('pix-list-id');
                have_list = true;    
            }
        }
        $("input, textarea, select").css('border-color',''); 
        $.each($(theform).serializeArray(), function(i, field) {
            values[pix_replace_chars(field.name)] = field.value;
            temp_str += pix_replace_chars(field.name) + ": " + field.value + "\n";
            if(field.value=="" && $(field).attr('required')){
            	field.css('border-color','red');
                proceed = false;
            }
        });
        if(proceed){   
            if(have_type){ values['pixfort_form_type'] = the_type; }
            if(have_list){ values['pixfort_form_list'] = the_list; }

            // Get the 'action' URL from the form
            action_url = $(theform).attr( "action" );

            console.log("Data: "+JSON.stringify(values));

            // Send the data to the server using the POST method
            $.post(action_url, values, function(result, status, xhr){
                // Handle a successful POST request
                $(theform).find('.alert').remove();
                setTimeout(
                    function(){
                        console.log("Form sent successfully. Response:"+result.text)
                        // Show the download links and hide the signup form
                        $("#signup_result").show();
                        $("#signup_form").hide();

                        if(is_confirm){ $(confirm_pop).modal('show'); }
                        if(is_redirect){ window.location.href = redirect_link; }
                        
                        // Create a success alert
                        /* var alert_msg = '<div class="alert alert-success" role="alert" style="display:none;">'+result.text+'</div>';
                         * $("#signup_result").prepend(alert_msg);
                         * $('.alert').slideDown();
                         */

			/* Cafelli/Stack Overflow Ad Conversion Tracking */
			var axel = Math.random()+"";
			var a = axel * 10000000000000;
			var stackoverflow_code = "<img src=\"https://pubads.g.doubleclick.net/activity;xsp=4718423;ord=" + a + "?\" width=1 height=1 border=0>";
			stackoverflow_code += "<noscript>";
			stackoverflow_code += "<img src=\"https://pubads.g.doubleclick.net/activity;xsp=4718423;ord=1?\" width=1 height=1 border=0>";
			stackoverflow_code += "</" + "noscript>";
			$("#signup_result").append(stackoverflow_code);
                    }, 500);
            }, 'html')
            // Handle errors returned from the server to the POST request
            .fail(function(xhr, status, error) {
                console.log("Error from form submission. Server Response: Error: "+status+ " " + error + " " + xhr.status + " " + xhr.statusText)
                var alert_msg = '<div class="alert alert-danger" role="alert" style="display:none;"> Error: '+status+ ' ' + error + ' ' + xhr.status + ' ' + xhr.statusText + '</div>';
                $(theform).prepend(alert_msg);
                $('.alert').slideDown();
            });
        }
    });
	$("input, textarea, select").keyup(function() { 
		$(this).css('border-color',''); 
        $('.alert').slideUp();
    });

    $('.pix-countdown').each(function(){
        var self = $(this);
        var endDate = $(this).attr('data-date');
        self.countdown({
            date: endDate,
            render: function(data) {
                $.each(data, function(key, value) {
                    self.find('.pix-count-'+key).html(value);
                });
            },
            onEnd: function(){
                if($(this.el).attr('data-redirect')){
                    window.location.href = $(this.el).attr('data-redirect');
                }
                if($(this.el).attr('data-popup')){
                    $($(this.el).attr('data-popup')).modal('show');
                }
            }
        });
    });


	
	var width = $(window).width();
	if(($('.pix_scroll_menu').length==0)&&(width>768)){
		pix_scroll_menu();
	}
	
	pix_mobile_bg();
	
	

	$(window).on('resize', function(){
		if($(this).width() != width){
			width = $(this).width();
       		if(width>768){
       			if($('.pix_scroll_menu').length==0){
       				pix_scroll_menu();
       			}
       		}else{
       			if($('.pix_scroll_header').length>0){
					$('.pix_scroll_menu').remove();
				}
       		}
       		$('.pix_scroll_menu, .pix_nav_menu').find(".dropdown, .btn-group").removeClass('hover_open');
       		$('.pix_scroll_menu, .pix_nav_menu').find(".dropdown, .btn-group").removeClass('open');
		}
	});

	$(document).on({
        mouseenter: function () {
        	if(width>768){
	        	$('.pix_scroll_menu').find(".dropdown, .btn-group").removeClass('hover_open');
	            var dropdownMenu = $(this).children(".dropdown-menu");
	            if(dropdownMenu.is(":visible")){
	                dropdownMenu.parent().toggleClass("hover_open");
	            }
        	}
        },
        mouseleave: function () {
            if(width>768){
	            var dropdownMenu = $(this).children(".dropdown-menu");
	            if(dropdownMenu.is(":visible")){
	                dropdownMenu.parent().toggleClass("hover_open");
	            }
	            $('.pix_scroll_menu').find(".dropdown, .btn-group").removeClass('hover_open');
	        }
        }
    }, ".dropdown, .btn-group");

	$(window).scroll(function() {
		if (jQuery(window).scrollTop() >= 400) {
			$('.pix_scroll_menu').css({
				'top' : '0px',
				'visibility': 'visible',
			});
			$('.pix_scroll_menu').find(".dropdown, .btn-group").children(".dropdown-menu").css({
				'display': 'block'
			});
			$('.pix_scroll_menu').find(".dropdown, .btn-group").removeClass('open');
		} else {
			$('.pix_scroll_menu').css({
				'top' : '-80px',
				'visibility': 'hidden'
			});
			$('.pix_scroll_menu').find(".dropdown, .btn-group").removeClass('hover_open');
			$('.pix_scroll_menu').find(".dropdown, .btn-group").children(".dropdown-menu").css({
				'display': 'none'
			});
			$('.pix_nav_menu').find(".dropdown, .btn-group").removeClass('open');
		}	
	});


    if($('body').find('.pix_nav_menu')){
        var header_sec = $('body').find('.pix_section.pix_nav_menu');
        if(header_sec.hasClass('pix-over-header')||header_sec.hasClass('pix-fixed-top')){
            var sec_index = header_sec.index();
            var header_h = header_sec.outerHeight();
            sec_index++;
            var sec = $('body > .pix_section').eq(sec_index);
            if(sec.length){
                if(!sec.attr('data-pix-offset')){
                    sec.attr('data-pix-offset', header_h);
                    var sec_padding = sec.css('padding-top').replace("px", "");
                    sec_padding=Number(sec_padding)+Number(header_h);
                    //sec.css('padding-top', sec_padding);
                }
            }
        }
    }

});


// ===========================================================
// Functions
// ===========================================================

function pix_scroll_menu(){
    if($('.pix_scroll_header').length>0){
        var logo_img = 'images/showcase/logo-thin.png';
        var logo_text = false;
        if($('.pix-logo-img').length>0){
            logo_img = $('.pix-logo-img')[0].src;
            pix_fix_heights();
        }else{
            logo_text = $('.logo-img.logo-img-a').html();
        }
        var scroll_bg = 'background: #fff;';
        if($('.pix_scroll_header').attr('data-scroll-bg')){
            scroll_bg = 'background: '+$('.pix_scroll_header').attr('data-scroll-bg')+';';
        }
        var nav_menu = '';
        if($('#pix-header-nav').length>0){
            nav_menu = $('<div>').append($('#pix-header-nav').clone().attr('id', 'pix-scroll-nav').addClass('navbar-right').addClass('pix-adjust-scroll').css('margin-top', 0)).html();
        }
        var header_btn = false;
        var header_btn_div = "";
        if($('#pix-header-btn').length>0){
            header_btn_div = '<div class="col-md-2"><div class="pix-content pix-adjust-scroll text-right">';
            var btns = $('#pix-header-btn').clone();
            btns.find('a').css('margin-top',0);
            header_btn_div += btns.html();
            header_btn_div += '</div></div>';
            header_btn=true;
        }

        var scroll_col = "col-md-12";
        if(header_btn){
            scroll_col = "col-md-10";
        }

        var sh2 = '<div class="pix_scroll_menu pix_menu_hidden" style="padding-top: 10px; padding-bottom: 10px; '+scroll_bg+'">'+
            '<div class="container">'+
            '<div class="row">'+
            '<div class="pix-inner-col '+scroll_col+'">'+
            '<div class="pix-content">'+
            '<nav class="navbar navbar-default pix-no-margin-bottom pix-navbar-default">'+
            '<div class="container-fluid">'+
            '<div class="navbar-header">'+
            '<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">'+
            '<span class="sr-only">Toggle navigation</span>'+
            '<span class="icon-bar"></span>'+
            '<span class="icon-bar"></span>'+
            '<span class="icon-bar"></span>'+
            '</button>';
        if(!logo_text){
            sh2 += '<a class="navbar-brand logo-img pix-adjust-scroll" href="#"><img src="'+logo_img+'" alt="" class="img-responsive scroll_logo_img"></a>';
        }else{
            sh2 += '<a class="navbar-brand logo-img logo-text pix-adjust-scroll" href="#">'+logo_text+'</a>';
        }
        sh2 += '</div>'+
            '<div class="collapse navbar-collapse">'+
            nav_menu+
            '</div>'+
            '</div>'+
            '</nav>'+
            '</div>'+
            '</div>';
        if(header_btn){
            sh2 += header_btn_div;
        }
        sh2+='</div>'+
            '</div>'+
            '</div>'+
            '</div>';
        $('body').append(sh2);
        pix_fix_scroll_heights();
    }
}

function pix_mobile_bg(){
	$('.pix_nav_menu').each(function(){
		$(this).attr('data-main-bg',$(this).css('background'));
	});
	var width = $(window).width();
	if(width<768){
		$('.pix_nav_menu').each(function(){
	        if($(this).attr('data-scroll-bg')){
	        	var bg = $(this).attr('data-scroll-bg');

	        	$(this).css('background',bg);
	        }
		});
	}
	$(window).on('resize', function(){
		if($(this).width() != width){
			width = $(this).width();
			if(width<768){
				$('.pix_nav_menu').each(function(){
			        if($(this).attr('data-scroll-bg')){
			        	var bg = $(this).attr('data-scroll-bg');
			        	$(this).css('background-color',bg);
			        }
				});
			}else{
				$('.pix_nav_menu').each(function(){
					$(this).css('background-color',$(this).attr('data-main-bg'));
				});
			}
		}
	});
}
function pix_fix_heights(){
    $('.pix_nav_menu').each(function(){
        var max_h = 0;
        $(this).find('.pix-adjust-height').each(function(item){
            if($(this).outerHeight()>max_h){max_h=$(this).outerHeight();}
        });
        if(max_h>0){
            $(this).find('.pix-adjust-height').each(function(item){
                var item_h = +$(this).outerHeight();
                if(item_h<max_h){
                    var diff = max_h - item_h;
                    diff /=2;
                    $(this).css('margin-top', diff);
                }
            });
        }
	});
}
function pix_fix_scroll_heights(){
	var max_h = 0;
	$('.pix-adjust-scroll').each(function(item){
		if($(this).outerHeight()>max_h){max_h=$(this).outerHeight();}
	});
	if(max_h>0){
		var logo_h = $('.logo-img-a').outerHeight();
		$('.pix-adjust-scroll').each(function(item){
			var item_h = $(this).outerHeight();
			if(item_h<max_h){
				var diff = max_h - item_h;
				diff /=2;
				$(this).css('margin-top', diff);
			}
		});
	}
}


function pix_disable_nav_click(){
	$('.pix_scroll_menu, .pix_nav_menu').find(".dropdown, .btn-group").on('click', function(e){
		if($(window).width()>768){
			e.stopPropagation();
	    	e.preventDefault();
	    	return false;
    	}
	});
}

function pix_replace_chars(string){
	return string.replace(/[^a-zA-Z0-9]/g,'_');
}
