
(function ($, window) {

	$.fn.netlivaCommenter = function(settings)
	{
		settings = $.extend({
			create_url: $(this).data("createUrl"),
			refresh_url: $(this).data("refreshUrl")
		}, settings);

		var commenter = {
			// ===============
			area: null,
			settings : {
				create_url: null,
			},
			counts: {
				limit: 5,
				total: 0,
				loaded: 0,
				last_load: 0,
				last_id: 0,
			},
			// === Elements ===
			e : {
				input_area		: ".netliva-comment-input-area",
				show_old_btn	: ".netliva-show-old-comments-btn",
			},
			t : {
				input: '<div class="d-flex comment-input-container">\
						<textarea class="form-control netliva-comment-input" rows="1" placeholder="Yorum"></textarea>\
						<button class="btn btn-xs btn-success netliva-send-comment-btn"><i class="fa fa-paper-plane"></i></button>\
					</div>\
				',
			},
			loaders	: {
				ring: '<div class="netliva-lds-ring"><div></div><div></div><div></div><div></div></div>',
				blocks: '<div class="netliva-lds-blocks"><div></div><div></div><div></div></div>',
			},

			// === FUNCTIONS ===
			init: function (area, settings)
			{
				this.area = area;
				this.settings = $.extend(this.settings, settings);
				this.area.find(this.e.show_old_btn).click(this.actions.show_comment);
				commenter.create_comment_input();
				commenter.load_comments();
			},
			create_comment_input: function () {
				$input_conainer = $(commenter.t.input);
				$input_conainer.find('textarea').keydown(this.actions.autosize).keydown(this.actions.enter_send)
				$input_conainer.find("button").click(this.actions.send);
				this.area.find(commenter.e.input_area).append($input_conainer);
			},
			load_comments: function (position)
			{
				if (typeof position === "undefined") position = "before";

				commenter.area.find(".loader-block").remove();
				if (position == "before") commenter.area.find("ul").prepend("<li class='text-center loader-block'>"+commenter.loaders.blocks+"</li>");
				else commenter.area.find("ul").append("<li class='text-center loader-block'>"+commenter.loaders.blocks+"</li>");

				$.ajax({
					url:commenter.settings.refresh_url+"/"+commenter.counts.limit+"/"+commenter.counts.last_id, dataType: "json", type: "post",
					success: function (response) {
						commenter.counts.loaded += response.count;
						commenter.counts.total   = response.total;
						commenter.counts.last_id = response.lastId;
						if (position == "before") commenter.area.find("ul").prepend(response.html);
						else commenter.area.find("ul").html(response.html);
						commenter.update_show_btn();
						commenter.area.find("ul > li:not(.binded)").each(commenter.actions.comment_btns)
					},
				    complete: function () {
					    commenter.area.find(".loader-block").remove();
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
			send: function ($comment_area) {
				var $input = $comment_area.find("textarea");
				$input.prop("disabled",true);
				$input.before(commenter.loaders.ring);
				$.ajax({
					url: commenter.settings.create_url,
					data:{
						comment : $input.val(),
						group   : commenter.area.data("group"),
					},
					dataType:"json", type:"post",
					success:function(response)
					{
						commenter.counts.limit   = 5;
						commenter.counts.loaded  = 0;
						commenter.counts.total   = 0;
						commenter.counts.last_id = 0;
						commenter.load_comments("over");
						$input.val("").prop("disabled",false);
					},
					error: function (response) {
						commenter.show_error(response);
						$input.prop("disabled",false);
					},
					complete: function () {
						commenter.area.find(".netliva-lds-ring").remove();
						$input.focus();
					}
				});
			},
			show_history: function ($line)
			{
				commenter.modal.open({
					title: "Yorum Geçmişi",
					ajax: {url:$line.data("historyUrl")},
				});
			},
			delete_comment: function ($line)
			{
				commenter.modal.open({
					title: "Silme Onayı",
					content: "Yorumu silmek istediğinizden emin misiniz?",
					buttons: [{
						label: "SİL",
						action: function () {
							$.ajax({
								url: $line.data("deleteUrl"), data:{}, dataType: "json", type: "post",
								success: function (response) {
									$line.remove();
									commenter.modal.close();
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
				$input.before(commenter.loaders.ring);

				$.ajax({
					url:$line.data("updateUrl"),
					data:{ comment:$input.val() },
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
				commenter.modal.open({
					class: "danger text-white",
					title: "Hata!",
					content: "<div class='text-center m-2'>"+text+"</div>",
					buttons: [{label:"Kapat", action:"close"}]
				})
			},
			actions: {
				enter_send: function (e) {
					if(e.which === 13 && !e.shiftKey)
					{
						commenter.send($(this).closest(".comment-input-container"));
						return false;
					}
				},
				autosize: function (e) {
					var el = $(this);
					setTimeout(function(){
						el.css({"height": "auto"});
						el.css({"height": el.prop("scrollHeight") + 'px'});
					},0);
				},
				send: function () {
					commenter.send($(this).closest(".comment-input-container"))
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
				},
				show_comment: function () {
					var $btn = commenter.area.find(commenter.e.show_old_btn);
					var left = commenter.counts.total - commenter.counts.loaded;
					$btn.html((commenter.counts.limit>left?left:commenter.counts.limit)+" Adet "+(commenter.counts.limit>3?"Daha ":"")+"Yükleniyor...");
					commenter.load_comments();

					if (commenter.counts.limit<15) commenter.counts.limit += 2;
				},
			},
			modal: {
				open: function (options){
					options = $.extend({content: '', title: '', class: 'info', buttons: null, ajax:null, }, options);

					if (!$("#netliva_comment_modal").length) commenter.modal.create();

					$("#netliva_comment_modal .modal-title").text(options.title);
					if (options.ajax)
					{
						$("#netliva_comment_modal .modal-body").html('<div class="text-center">'+commenter.loaders.blocks+'<div><strong>Yükleniyor...</strong></div></div>');
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
					if (options.buttons) commenter.modal.create_buttons(options.buttons);
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
		};


		$(this).addClass("binded");
		commenter.init($(this), settings);

	};


	init = function()
	{
		$(".netliva-comments-area:not(.binded)").each(function () {
			$(this).netlivaCommenter();
		});
	};

	$(document).ajaxComplete(init);
	jQuery(function ($) {init();});

})(jQuery, window);

