
(function ($, window) {

	var netliva_commenter_global = {
		loaders	: {
			ring: '<div class="netliva-lds-ring"><div></div><div></div><div></div><div></div></div>',
			blocks: '<div class="netliva-lds-blocks"><div></div><div></div><div></div></div>',
		},
		modal: {
			open: function (options){
				options = $.extend({content: '', title: '', class: 'info', buttons: null, ajax:null, }, options);

				if (!$("#netliva_comment_modal").length) netliva_commenter_global.modal.create();

				$("#netliva_comment_modal .modal-title").text(options.title);
				if (options.ajax)
				{
					$("#netliva_comment_modal .modal-body").html('<div class="text-center">'+netliva_commenter_global.loaders.blocks+'<div><strong>Yükleniyor...</strong></div></div>');
					$.ajax({
						url:options.ajax.url,
						data: typeof options.ajax.data !== 'undefined' ? options.ajax.data : {},
						dataType: "html", type: "post",
						success: function (response) {
							$("#netliva_comment_modal .modal-body").html(response);
						}
					});
				}
				else
					$("#netliva_comment_modal .modal-body").html(options.content);

				$("#netliva_comment_modal .modal-header").removeClass().addClass("modal-header bg-"+options.class);
				$("#netliva_comment_modal").modal("show");
				if (options.buttons) netliva_commenter_global.modal.create_buttons(options.buttons);
			},
			close: function () {
				$("#netliva_comment_modal").modal("hide");
			},
			create: function () {
				$("body").append('\
					<div class="modal fade" id="netliva_comment_modal" tabindex="-1" role="dialog" aria-labelledby="netliva_comment_modal" aria-hidden="true">\
					  <div class="modal-dialog" role="document">\
						<div class="modal-content">\
						  <div class="modal-header">\
							<h5 class="modal-title">Modal title</h5>\
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">\
							  <span aria-hidden="true">&times;</span>\
							</button>\
						  </div>\
						  <div class="modal-body"> ... </div>\
						  <div class="modal-footer bg-light" style="display: none;"></div>\
						</div>\
					  </div>\
					</div>\
				');
			},
			create_buttons: function($btns)
			{
				if ($btns !== null)
				{
					$("#netliva_comment_modal").find('.modal-footer').show();
					$("#netliva_comment_modal").find('.modal-footer').html('');
					$.each($btns, function (index, button)
					{
						var btnClass = "success";
						if (typeof (button.class) !== "undefined")
							btnClass = button.class;
						else if (button.action === 'close')
							btnClass = "danger";

						var $btnTxt = '<button id="netliva_comment_modal_btn_' + index + '"';
						if (button.action === 'close')
							$btnTxt += 'data-dismiss="modal"';
						$btnTxt += 'class="btn btn-' + btnClass + '" type="button">' + button.label + '</button>';

						$("#netliva_comment_modal").find('.modal-footer').append($btnTxt);

						if (typeof (button.action) === "function")
						{
							$("#netliva_comment_modal_btn_" + index).click(button.action);
						}
					});
				}

			}
		}
	}

	$.fn.netlivaCommenterReactions = function(settings)
	{
		settings = $.extend({
			emotions        : $(this).data("emotions"),
			default_emotion : $(this).data("defaultEmotion"),
			history_url     : $(this).data("historyUrl"),
			add_url         : $(this).data("addUrl"),
			comment_id      : $(this).data("commentId"),
		}, settings);

		var reactor = {
			settings : {
				emotions        : null,
				default_emotion : null,
				history_url     : null,
				add_url         : null,
				comment_id      : null,
			},
			area: null,
			init: function (area, settings) {
				this.settings = $.extend(this.settings, settings);
				reactor.area = area;
				reactor.reaction_buttons();
				reactor.reaction_initialize();
			},
			reaction_initialize: function ()
			{
				var $statsArea = reactor.area.find('.nc-reactions-stat');
				var emosh = $statsArea.data('emotions');
				var $ul = $('<ul></ul>')
				var total = 0;
				$.each(emosh, function (emo, count) {
					if (typeof reactor.settings.emotions[emo] !== 'undefined' )
					{
						total += count;
						$ul.append('<li>'+reactor.settings.emotions[emo].emoji+'</li>');
					}
				});
				$statsArea.html("");
				if (total)
				{
					$ul.append('<li class="total_info">'+total+'</li>');
					$ul.click(function () {
						netliva_commenter_global.modal.open({
							title: "İfadeler",
							ajax: { url: reactor.settings.history_url },
						});
					});
					$ul.appendTo($statsArea);
				}
			},
			reaction_buttons: function ()
			{
				var $btnArea = reactor.area.find(".nc-reactions-button");
				$btnArea.hover(function () {
					const alanfark = $(this).closest('.netliva-comments-area').width()
						- (
							(
								$(this).offset().left
								- $(this).closest('.netliva-comments-area').offset().left
							)
							+ $(this).find('.nc-reactions-box').width()
						);
					const left = alanfark < 0 ? alanfark + 20 : -($(this).find('.nc-reactions-box').width()/3);
					$(this).find('.nc-reactions-box').css('left', left+"px");
				});
				// console.log($btnArea);
				$btnArea.find(".nc-reaction").click(function(){
					// console.log($btnArea);
					var emoKey = $(this).data("emoKey");
					var emoTex = $(this).data("reaction");
					$btnArea.find(".nc-reactions-button-text").append(netliva_commenter_global.loaders.ring);
					$.ajax({
						url:reactor.settings.add_url, data:{reaction: emoKey}, dataType: "json", type: "post",
						success: function (response) {
							$btnArea.find(".netliva-lds-ring").remove();
							if (response.type == 'remove')
							{
								$btnArea.find(".nc-reactions-button-emo").text(reactor.settings.default_emotion);
								$btnArea.find(".nc-reactions-button-text").text('İfade Bırak').css('color', '#666');
							}
							else
							{
								if (typeof reactor.settings.emotions[emoKey] !== 'undefined' )
								{
									$btnArea.find(".nc-reactions-button-emo").text(reactor.settings.emotions[emoKey].emoji);
									$btnArea.find(".nc-reactions-button-text").text(emoTex).css('color', reactor.settings.emotions[emoKey].color);
								}
							}
							reactor.area.find('.nc-reactions-stat').data('emotions', response.counts);
							reactor.reaction_initialize();
						}
					});
					return false;
				});
				$btnArea.find(".nc-reactions-button-emo, .nc-reactions-button-text").click(function(){
					if ($btnArea.find(".nc-reactions-button-emo").text() != reactor.settings.default_emotion)
					{
						$.ajax({
								   url:reactor.settings.add_url, data:{reaction: null}, dataType: "json", type: "post",
								   success: function (response) {
									   $btnArea.find(".nc-reactions-button-emo").text(reactor.settings.default_emotion);
									   $btnArea.find(".nc-reactions-button-text").text('İfade Bırak').css('color', '#666');
									   reactor.area.find('.nc-reactions-stat').data('emotions', response.counts);
									   reactor.reaction_initialize();
								   }
							   });
					}
					return false;
				});
			},
		}


		if ($(this).hasClass('nc-reactions-line') && !$(this).hasClass('binded'))
		{
			$(this).addClass("binded");
			reactor.init($(this), settings);
		}
		else if ($(this).find('.nc-reactions-line').length)
		{
			$(this).find('.nc-reactions-line').each(function () {
				$(this).netlivaCommenterReactions();
			});
		}
	}

	$.fn.netlivaCommenter = function(settings)
	{
		settings = $.extend({
			create_url: $(this).data("createUrl"),
			refresh_url: $(this).data("refreshUrl"),
			new_coll_url: $(this).data("newcollUrl"),
			predefined_texts: $(this).data("predefinedTexts"),
			preload_comments: $(this).data("preloadComments"),
			removeme_url: $(this).data("removemeUrl"),
			my_user: $(this).data("myUser"),
			options: $(this).data("options"),
		}, settings);

		var commenter = {
			// ===============
			area: null,
			settings : {
				create_url       : null,
				new_coll_url     : null,
				removeme_url     : null,
				refresh_url      : null,
				users            : null,
				all_users        : null,
				my_user          : null,
				options          : null,
				predefined_texts : null,
				preload_comments : null,
			},
			counts: {
				page: 1,
				total: 0,
				loaded: 0,
			},
			// === Elements ===
			e : {
				input_area		: ".netliva-comment-input-area",
				show_old_btn	: ".netliva-show-old-comments-btn",
				collaborators	: ".netliva-comment-collaborators-area",
			},
			t : {
				input: '<div class="d-flex comment-input-container">\
						<ul class="predefined-texts-area"></ul>\
						<textarea class="form-control netliva-comment-input" rows="1" placeholder="Yorum"></textarea>\
						<button class="btn btn-xs btn-success netliva-send-comment-btn"><i class="fa fa-paper-plane"></i></button>\
					</div>\
				',
			},

			// === FUNCTIONS ===
			init: function (area, settings)
			{
				this.area = area;
				this.settings = $.extend(this.settings, settings);
				this.area.find(this.e.show_old_btn).click(this.actions.show_comment);
				commenter.create_comment_input();
				if (this.settings.preload_comments)
				{
					this.counts.total   = this.settings.preload_comments.total;
					this.counts.loaded  = this.settings.preload_comments.loaded;
				}
				else commenter.load_comments();
				commenter.initalize_collaborators();
				commenter.area.find('.netliva-comment-input').focus(function () {
					if (!commenter.area.hasClass('comment-input-focus')) commenter.area.addClass('comment-input-focus')
					commenter.actions.pta.show();
				});
				$(document).trigger('netliva:commenter:init', [area, commenter])
			},
			stringToColour: function(str) {
				var hash = 0, i;
				for (i = 0; i < str.length; i++) {
					hash = str.charCodeAt(i) + ((hash << 5) - hash);
				}
				var colour = '#';
				for (i = 0; i < 3; i++) {
					var value = (hash >> (i * 8)) & 0xFF;
					colour += ('00' + value.toString(16)).substr(-2);
				}
				return colour;
			},
			pickTextColorBasedOnBgColorAdvanced : function (bgColor, lightColor, darkColor) {
				var color = (bgColor.charAt(0) === '#') ? bgColor.substring(1, 7) : bgColor;
				var r = parseInt(color.substring(0, 2), 16); // hexToR
				var g = parseInt(color.substring(2, 4), 16); // hexToG
				var b = parseInt(color.substring(4, 6), 16); // hexToB
				var uicolors = [r / 255, g / 255, b / 255];
				var c = uicolors.map(function(col) {
					if (col <= 0.03928) {
						return col / 12.92;
					}
					return Math.pow((col + 0.055) / 1.055, 2.4);
				});
				var L = (0.2126 * c[0]) + (0.7152 * c[1]) + (0.0722 * c[2]);
				return (L > 0.179) ? darkColor : lightColor;
			},
			getInitialsOfName: function (name) {
				words = name.split(" ")
				if (words.length === 1)
					return words[0].substr(0,2);

				return words[0].substr(0,1)+words[words.length-1].substr(0,1);
			},
			initalize_collaborators: function () {
				var $e = this.area.find(this.e.collaborators);
				commenter.settings.users = $e.data("users");
				commenter.settings.all_users = $e.data("allAuthors");
				$e.find(".netliva-comment-collaborators-add-btn").click(this.actions.add_collaborators);
				$e.find(".netliva-comment-collaborators-remove-me-btn").click(this.actions.remove_me_from_collaborators);

				this.prepare_collaborators();
			},
			prepare_collaborators: function () {
				var $collaborators_ul = commenter.area.find(commenter.e.collaborators).find('ul');
				$collaborators_ul.html("");
				var flag = false;
				$.each(commenter.settings.users, function (key, user) {
					photo = '<i>'+commenter.getInitialsOfName(user.name)+'</i>'
					if (user.photo)
						photo = '<img src="'+user.photo+'" class="collaborator_avatar" />';

					var bg = commenter.stringToColour(user.name);
					var fg = commenter.pickTextColorBasedOnBgColorAdvanced(bg, '#FFFFFF', '#000000');
					$collaborators_ul.append('<li data-user-id="'+user.id+'" style="background-color:'+bg+';color:'+fg+';">'+photo+'<span>'+user.name+'</span></li>');
					if (user.id === commenter.settings.my_user.id) flag = true;
				});
				if (flag) commenter.area.find(".netliva-comment-collaborators-remove-me-btn").show();
				else commenter.area.find(".netliva-comment-collaborators-remove-me-btn").hide();
			},
			create_comment_input: function () {
				$input_conainer = $(commenter.t.input);
				$input_conainer.find('textarea').keydown(this.actions.autosize).keydown(this.actions.enter_send).keyup(this.actions.pta.control_visibility);
				$input_conainer.find("button").click(this.actions.send);
				$.each(this.settings.predefined_texts, function (key, text) {
					$input_conainer.find(".predefined-texts-area").append('<li>'+text+'</li>');
				})
				$input_conainer.find(".predefined-texts-area li").click(this.actions.send_predefined_text);
				this.area.find(commenter.e.input_area).append($input_conainer);
			},
			load_comments: function (position)
			{
				if (typeof position === "undefined") position = "before";

				commenter.area.find(".loader-block").remove();
				if (position == "before") commenter.area.find("ul.netliva-comment-list").prepend("<li class='text-center loader-block'>"+netliva_commenter_global.loaders.blocks+"</li>");
				else commenter.area.find("ul.netliva-comment-list").append("<li class='text-center loader-block'>"+netliva_commenter_global.loaders.blocks+"</li>");

				$.ajax({
					url:commenter.settings.refresh_url+"/"+commenter.counts.page, dataType: "json", type: "post", data: {options: commenter.settings.options},
					success: function (response) {
						commenter.counts.loaded += response.count;
						commenter.counts.total   = response.total;
						if (position == "before") commenter.area.find("ul.netliva-comment-list").prepend(response.html);
						else commenter.area.find("ul.netliva-comment-list").html(response.html);
						commenter.update_show_btn();
						commenter.area.find("ul.netliva-comment-list > li:not(.binded)").each(commenter.actions.comment_btns)

						if (commenter.counts.total && commenter.area.hasClass("no-comment")) commenter.area.removeClass("no-comment");
						else if (!commenter.counts.total && !commenter.area.hasClass("no-comment")) commenter.area.addClass("no-comment");
					},
				    complete: function () {
					    commenter.area.find(".loader-block").remove();
						commenter.area.find(".firstInitLoader").remove();
				    },
				});
			},
			update_show_btn: function ( ) {
				// butonu gizleme ve yazısını değiştirme
				var $btn = commenter.area.find(commenter.e.show_old_btn);

				$btn.html('<i class="fa fa-check"></i> Yüklendi.');
				setTimeout(function () {
					if (commenter.counts.total <= commenter.counts.loaded) $btn.hide();
					else $btn.show().html('Eski Yorumları Göster ('+(commenter.counts.total-commenter.counts.loaded)+' adet mevcut)');
				},1000);
			},
			send: function ($comment_area, after_focus) {
				$(document).trigger('netliva:commenter:send:click', [$comment_area, commenter]);
				var $input = $comment_area.find("textarea");
				$input.prop("disabled",true);
				$input.before(netliva_commenter_global.loaders.ring);
				$.ajax({
					url: commenter.settings.create_url,
					data:{
						comment : $input.val(),
						group   : commenter.area.data("group"),
					},
					dataType:"json", type:"post",
					success:function(response,  textStatus, jqXHR)
					{
						$(document).trigger('netliva:commenter:send:success', [$comment_area, response,  textStatus, jqXHR, commenter]);
						commenter.counts.page    = 1;
						commenter.counts.loaded  = 0;
						commenter.counts.total   = 0;
						commenter.load_comments("over");
						$input.val("").prop("disabled",false);

						// kullanıcı yoksa ekle
						if (!commenter.settings.users.find(function (user) { return user.id === commenter.settings.my_user.id }))
						{
							commenter.settings.users.push(commenter.settings.my_user);
							commenter.prepare_collaborators();
						}

					},
					error: function (jqXHR, textStatus) {
						$(document).trigger('netliva:commenter:send:error', [$comment_area, jqXHR, textStatus, commenter]);
						commenter.show_error(jqXHR);
						$input.prop("disabled",false);
					},
					complete: function (jqXHR, textStatus) {
						$(document).trigger('netliva:commenter:send:complete', [$comment_area, jqXHR, textStatus, commenter]);
						commenter.area.find(".netliva-lds-ring").remove();
						if (after_focus) $input.focus();
					}
				});
			},
			show_history: function ($line)
			{
				netliva_commenter_global.modal.open({
					title: "Yorum Geçmişi",
					ajax: {url:$line.data("historyUrl")},
				});
			},

			delete_comment: function ($line)
			{
				netliva_commenter_global.modal.open({
					title: "Silme Onayı",
					content: "Yorumu silmek istediğinizden emin misiniz?",
					buttons: [{
						label: "SİL",
						action: function () {
							$.ajax({
								url: $line.data("deleteUrl"), data:{}, dataType: "json", type: "post",
								success: function (response) {
									$line.remove();
									netliva_commenter_global.modal.close();
								}
							});
						}
					},{
						label: "Vazgeç",
						action: "close",
					}]
				});
			},
			update: function ($line) {
				var $input = $line.find(".comment-input-container textarea");
				$input.prop("disabled",true);
				$input.before(netliva_commenter_global.loaders.ring);

				$.ajax({
					url:$line.data("updateUrl"),
					data:{ comment:$input.val(), options: commenter.settings.options },
					dataType:"json", type:"post",
					success:function(response)
					{
						$line.html($(response.html).html());
						$line.data("comment",$input.val());
						$line.each(commenter.actions.comment_btns)
						commenter.close_update($line);
					},
					error: function (response) {
						commenter.show_error(response);
						$input.prop("disabled",false);
						setTimeout(function () {
							$("#netliva_comment_modal_btn_0").click(function () {
								setTimeout(function () { $input.focus(); },10);
							});
						},100)
					},
					complete: function () {
						$line.find(".netliva-lds-ring").remove();
					}
				});
			},
			update_comment: function ($line) {
				$line.find(".netliva-a-comment").hide();
				$input_conainer = $(commenter.t.input);
				$input_conainer.find("button").after('<button class="btn btn-xs btn-danger netliva-cancel-comment-btn ml-1"><i class="fa fa-times"></i></button>');
				$input_conainer
					.find('textarea')
					.keydown(this.actions.autosize)
					.keydown(this.actions.update_keydown)
					.keydown()
					.text($line.data("comment"));
				$input_conainer.find(".btn-success").click(this.actions.update);
				$input_conainer.find(".btn-danger").click(this.actions.update_cancel);
				setTimeout(function () {
					$input_conainer.find('textarea').focus();
				},10);
				$line.append($input_conainer);
				this.area.find(commenter.e.input_area).hide();
			},
			close_update: function ($line) {
				$line.find(".netliva-a-comment").show();
				$line.find(".comment-input-container").remove();
				this.area.find(commenter.e.input_area).show();
			},
			show_error: function (response)
			{
				var text = "";
				if (typeof response.responseJSON == 'undefined')
					text = "Yorum eklerken bir hata oluştu!";
				else if (typeof response.responseJSON.errors != 'undefined')
				{
					text = "";
					$.each(response.responseJSON.errors, function (i, err) {
						text += err+"<br />";
					})

				}
				netliva_commenter_global.modal.open({
					class: "danger text-white",
					title: "Hata!",
					content: "<div class='text-center m-2'>"+text+"</div>",
					buttons: [{label:"Kapat", action:"close"}]
				})
			},
			actions: {
				pta: {
					control_visibility: function () {
						if (commenter.area.find(commenter.e.input_area).find('textarea').val())
							commenter.actions.pta.hide();
						else
							commenter.actions.pta.show();
					},
					show: function () {
						if (commenter.area.find('.predefined-texts-area li').length)
							commenter.area.find('.predefined-texts-area').show();
					},
					hide: function () {
							commenter.area.find('.predefined-texts-area').hide();
					},
				},
				enter_send: function (e) {
					if(e.which === 13 && !e.shiftKey)
					{
						commenter.send($(this).closest(".comment-input-container"), true);
						return false;
					}
				},
				autosize: function (e) {
					var el = $(this);
					var originalHeight = el.height();

					// Görünmez bir klon oluştur
					var clone = el.clone()
						.css({
								 'position': 'absolute',
								 'visibility': 'hidden',
								 'height': 'auto',
								 'width': el.width() + 'px'
							 })
						.attr('tabindex', -1);

					// Klonu body'e ekle
					el.after(clone);

					setTimeout(function () {
						// Yeni yüksekliği klondan hesapla
						var newHeight = clone.prop("scrollHeight");
						// Klonu kaldır
						clone.remove();

						// Yüksekliği güncelle
						if (newHeight !== originalHeight) {
							el.css("height", newHeight + "px");
						}
					}, 0);
				},
				send: function () {
					commenter.send($(this).closest(".comment-input-container"), true)
				},
				send_predefined_text: function ()
				{
					commenter.area.find(commenter.e.input_area).find('textarea').val($(this).text());
					commenter.send($(this).closest(".comment-input-container"), false);
					commenter.actions.pta.hide();
				},
				update: function () {
					commenter.update($(this).closest("li"))
				},
				update_cancel: function (e) {
					commenter.close_update($(this).closest("li"));
					return false;
				},
				update_keydown: function (e) {
					if(e.which === 13 && !e.shiftKey)
					{
						commenter.update($(this).closest("li"));
						return false;
					}
					else if(e.which === 27 && !e.shiftKey)
					{
						commenter.close_update($(this).closest("li"));
						return false;
					}
				},
				comment_btns: function () {
					$(this).addClass("binded");
					$(this).find(".ntlv_user_name").each(function () {
						var color = commenter.stringToColour($(this).text());
						$(this).css("color", color);
						if ($(this).parent().hasClass('netliva-answer-comment'))
						{
							$(this).parent().css('border-color', color);
							$(this).parent().click(function(){
								$(this).css('max-height', 300);
								var e = $(this).css('max-height', 300);
								setTimeout(function () {
									e.removeClass('answer-overflow');
									e.css('max-height', 'none');
								},300);
							}).hover(function(){
								if ($(this).outerHeight() < $(this).prop('scrollHeight') && !$(this).hasClass("answer-overflow"))
									$(this).addClass('answer-overflow');
							});
						}
					});
					$(this).find(".netliva-comment-edit-btn").click(function(){
						commenter.update_comment($(this).closest("li"));
						return false;
					});
					$(this).find(".netliva-comment-delete-btn").click(function(){
						commenter.delete_comment($(this).closest("li"));
						return false;
					});
					$(this).find(".netliva-comment-history").click(function(){
						commenter.show_history($(this).closest("li"));
						return false;
					});
					// ifade butonları
					$(this).find(".nc-reactions-line").each(function(){
						$(this).netlivaCommenterReactions();
					});
					$(document).trigger('netliva:commenter:initline', [$(this), commenter]);
				},
				show_comment: function () {
					var $btn = commenter.area.find(commenter.e.show_old_btn);
					var left = commenter.counts.total - commenter.counts.loaded;
					$btn.html("Yükleniyor...");
					commenter.counts.page++;
					commenter.load_comments();
				},
				add_collaborators_close: function () {
					var $collaborators_ul = commenter.area.find(commenter.e.collaborators).find('ul');
					$collaborators_ul.find(".select_user_for_add").remove();
					commenter.area.find(commenter.e.collaborators).find(".netliva-comment-collaborators-add-btn").show();
				},
				add_collaborators: function () {
					commenter.area.find(commenter.e.collaborators).find(".netliva-comment-collaborators-add-btn").hide();

					var $collaborators_ul = commenter.area.find(commenter.e.collaborators).find('ul');
					var close_btn = '<svg class="ntlv-svg-icon" viewBox="0 0 20 20">  <path fill="none" d="M13.864,6.136c-0.22-0.219-0.576-0.219-0.795,0L10,9.206l-3.07-3.07c-0.219-0.219-0.575-0.219-0.795,0  c-0.219,0.22-0.219,0.576,0,0.795L9.205,10l-3.07,3.07c-0.219,0.219-0.219,0.574,0,0.794c0.22,0.22,0.576,0.22,0.795,0L10,10.795  l3.069,3.069c0.219,0.22,0.575,0.22,0.795,0c0.219-0.22,0.219-0.575,0-0.794L10.794,10l3.07-3.07  C14.083,6.711,14.083,6.355,13.864,6.136z M10,0.792c-5.086,0-9.208,4.123-9.208,9.208c0,5.085,4.123,9.208,9.208,9.208  s9.208-4.122,9.208-9.208C19.208,4.915,15.086,0.792,10,0.792z M10,18.058c-4.451,0-8.057-3.607-8.057-8.057  c0-4.451,3.606-8.057,8.057-8.057c4.449,0,8.058,3.606,8.058,8.057C18.058,14.45,14.449,18.058,10,18.058z"></path> </svg>'
					$collaborators_ul.append('<li class="select_user_for_add"><div class="netliva_collaborator_search_area"><input type="text" /><button class="netliva_collaborator_search_close_btn">'+close_btn+'</button></div><ul></ul></li>');
					$.each(commenter.settings.all_users, function (key, user) {
						var flag = false;
						$.each(commenter.settings.users, function (key2, user2) {
							if (user.id === user2.id) flag = true;
						});
						if (!flag)
						{
							var bg = commenter.stringToColour(user.name);
							var fg = commenter.pickTextColorBasedOnBgColorAdvanced(bg, '#FFFFFF', '#000000');
							photo = '<i style="background-color:'+bg+';color:'+fg+';">'+commenter.getInitialsOfName(user.name)+'</i>'
							if (user.photo)
								photo = '<img src="'+user.photo+'" class="collaborator_avatar" style="border-color:'+bg+';" />';

							$collaborators_ul.find("ul").append('<li data-key="'+key+'" data-user-id="'+user.id+'">'+photo+'<span>'+user.name+'</span></li>');
						}
					});
					$collaborators_ul.find(".select_user_for_add ul li").click(function(){
						data = commenter.settings.all_users[$(this).data("key")];
						commenter.settings.users.push(data);
						commenter.actions.add_collaborators_close();
						commenter.prepare_collaborators();
						$.ajax({
							url: commenter.settings.new_coll_url,
							data: {author: data.id},
							dataType:"json", type:"post",
							success:function(response) {
								commenter.prepare_collaborators();
							},
							error: function (response) {
								commenter.show_error(response);
							},
							complete: function () {
							}
						});
					});
					$collaborators_ul.find(".netliva_collaborator_search_close_btn").click(function(){
						commenter.actions.add_collaborators_close();
					});
					$collaborators_ul.find(".select_user_for_add input")
						.focus()
						.keyup(function(e)
						{
							var code = (e.keyCode ? e.keyCode : e.which);
							if (code === 27)
							{
								commenter.actions.add_collaborators_close();
								return;
							}
							var searchText = $(this).val();
							$collaborators_ul.find(".select_user_for_add ul > li").each(function()
							{
								var currentLiText = $(this).text(),
									showCurrentLi = currentLiText.toLowerCase().indexOf(searchText.toLowerCase()) !== -1;
								$(this).toggle(showCurrentLi);
							});
						});

				},
				remove_me_from_collaborators: function ()
				{
					$.ajax({
						url: commenter.settings.removeme_url,
						dataType:"json", type:"post",
						success:function(response) {
							commenter.settings.users = commenter.settings.users.filter(function(value, index, arr){ return value.id !== commenter.settings.my_user.id;});
							commenter.prepare_collaborators();
						},
						error: function (response) {
							commenter.show_error(response);
						},
						complete: function () {
						}
					});

				}
			},
		};


		$(this).addClass("binded");
		commenter.init($(this), settings);

	};


	init = function()
	{
		$(".netliva-comments-area:visible:not(.binded)").each(function () {
			$(this).netlivaCommenter();
		});
	};

	$(document).ajaxComplete(init);
	jQuery(function ($) {init();});

})(jQuery, window);

