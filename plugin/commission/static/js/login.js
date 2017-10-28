define(['core', 'tpl', 'biz/plugin/diyform'], function(i, j, k) {
	var l = {
		params: {
			applytitle: '',
			open_protocol: 0
		}
	};
	l.init = function(g, h) {
		l.params = $.extend(l.params, h || {});
		$('.btn-submit').click(function() {
			var c = $(this);
			if (c.attr('stop')) {
				return
			}
			var d = c.html();
			var e = false;
			var f = {};
			if ($(".diyform-container").length > 0) {
				e = k.getData('.page-commission-register .diyform-container');
				if (!e) {
					return
				}
				f = {
					memberdata: e
				}
			} else {
				if ($('#realname').isEmpty()) {
					FoxUI.toast.show('请填写您的姓名!');
					return
				}
				if (!$('#mobile').isMobile()) {
					FoxUI.toast.show('请填写正确手机号!');
					return
				}
				f = {
					'agentid': g,
					'realname': $('#realname').val(),
					'mobile': $('#mobile').val(),
					'weixin': $('#weixin').val()
				}
			}
			if (l.params.open_protocol == 1) {
				if (!$('#agree').prop('checked')) {
					FoxUI.toast.show('请阅读并了解【' + l.params.applytitle + '】!');
					return
				}
			}
			c.attr('stop', 1).html('正在处理...');
			i.json('commission/register', f, function(a) {
				if (a.status == 0) {
					c.removeAttr('stop').html(d);
					FoxUI.toast.show(a.result.message);
					return
				}
				var b = a.result;
				if (b.check == '1') {
					FoxUI.message.show({
						icon: 'icon icon-roundcheck success',
						content: "恭喜您审核通过!",
						buttons: [{
							text: '进入商城',
							extraClass: 'btn-success',
							onclick: function() {
								location.href = i.getUrl('')
							}
						}]
					})
				} else {
					FoxUI.message.show({
						icon: 'icon icon-info text-warning',
						content: "您的申请已经提交，请等待审核!",
						buttons: [{
							text: '先去商城逛逛',
							extraClass: 'btn-danger',
							onclick: function() {
								location.href = i.getUrl('')
							}
						}]
					})
				}
			}, true, true)
		});
		$("#btn-apply").unbind('click').click(function() {
			var a = $(".pop-apply-hidden").html();
			container = new FoxUIModal({
				content: a,
				extraClass: "popup-modal",
				maskClick: function() {
					container.close()
				}
			});
			container.show();
			$('.verify-pop').find('.close').unbind('click').click(function() {
				container.close()
			});
			$('.verify-pop').find('.btn').unbind('click').click(function() {
				container.close()
			})/*微 橙 微信商城系统*/
		})
	};
	return l
});