/**
 * Multi WhatsApp Support - 后台管理交互脚本
 */
(function($) {
  'use strict';

  $(document).ready(function() {
    var data = window.mwsAdminData || {};
    var i18n = data.i18n || {};

    // 1. 选项卡切换
    $('.mws-nav-tab').on('click', function(e) {
      e.preventDefault();
      var tabId = $(this).attr('data-tab');

      $('.mws-nav-tab').removeClass('is-active');
      $(this).addClass('is-active');

      $('.mws-tab-panel').removeClass('is-active');
      $('#' + tabId).addClass('is-active');

      if (history.pushState) {
        history.pushState(null, null, '#' + tabId);
      }
    });

    // 检查初始 URL Hash 激活对应 Tab
    if (window.location.hash) {
      var initialTab = window.location.hash.substring(1);
      var $targetTabLink = $('.mws-nav-tab[data-tab="' + initialTab + '"]');
      if ($targetTabLink.length) {
        $targetTabLink.trigger('click');
      }
    }

    // 2. 初始化 WordPress 原生取色器并同步更新实时预览
    $('.mws-color-picker').wpColorPicker({
      change: function() {
        setTimeout(updatePreviewColors, 50);
      },
      clear: function() {
        setTimeout(updatePreviewColors, 50);
      }
    });

    function updatePreviewColors() {
      var gStart = $('#mws_color_gstart').val() || '#075e54';
      var gMid   = $('#mws_color_gmid').val()   || '#128c7e';
      var gEnd   = $('#mws_color_gend').val()   || '#25d366';
      var accent = $('#mws_color_accent').val() || '#25d366';

      $('#mwsSimHeader').css('background', 'linear-gradient(135deg, ' + gStart + ' 0%, ' + gMid + ' 50%, ' + gEnd + ' 100%)');
      $('#mwsSimFab').css('background', 'linear-gradient(135deg, ' + gMid + ' 0%, ' + accent + ' 100%)');
      $('.mws-sim-btn').css('background', accent);
    }

    // 3. 通道列表拖拽排序 (jQuery UI Sortable)
    if ($.fn.sortable) {
      $('#mwsChannelList').sortable({
        handle: '.mws-channel-handle',
        axis: 'y',
        opacity: 0.75,
        update: function() {
          reindexChannels();
          updateLivePreview();
        }
      });
    }

    // 4. 新增客服通道
    $('#mwsAddChannelBtn').on('click', function(e) {
      e.preventDefault();
      var tplHtml = $('#mws-channel-template').html();
      var newIdx = 'new_' + Date.now();
      var currentCount = $('#mwsChannelList .mws-channel-item').length + 1;

      tplHtml = tplHtml.replace(/__INDEX__/g, newIdx);
      tplHtml = tplHtml.replace(/__BADGE__/g, currentCount);

      var $newItem = $(tplHtml).hide();
      $('#mwsChannelList').append($newItem);
      $newItem.slideDown(200);

      reindexChannels();
      updateLivePreview();

      $newItem.find('.mws-input-label').focus();
    });

    // 5. 删除客服通道
    $('#mwsChannelList').on('click', '.mws-btn-remove-channel', function(e) {
      e.preventDefault();
      var $items = $('#mwsChannelList .mws-channel-item');
      if ($items.length <= 1) {
        alert('请至少保留一个客服通道！');
        return;
      }

      var confirmMsg = i18n.confirmDelete || '确定要删除该客服通道吗？';
      if (!confirm(confirmMsg)) {
        return;
      }

      var $card = $(this).closest('.mws-channel-item');
      $card.slideUp(200, function() {
        $card.remove();
        reindexChannels();
        updateLivePreview();
      });
    });

    // 6. 重置序列索引，确保 PHP 接受整齐数组
    function reindexChannels() {
      $('#mwsChannelList .mws-channel-item').each(function(idx) {
        $(this).attr('data-index', idx);
        $(this).find('input').each(function() {
          var name = $(this).attr('name');
          if (name) {
            var updatedName = name.replace(/mws_settings\[channels\]\[[^\]]+\]/, 'mws_settings[channels][' + idx + ']');
            $(this).attr('name', updatedName);
          }
        });
      });
    }

    // 7. 实时效果预览同步器
    function updateLivePreview() {
      // 同步标题、副标题与底部背书
      var title = $('#mws_popover_title').val() || 'WhatsApp Support';
      var subtitle = $('#mws_popover_subtitle').val() || 'Online 24/7 Available';
      var footer = $('#mws_popover_footer').val();

      $('#mwsSimTitle').text(title);
      $('#mwsSimSubtitle').text(subtitle);

      if (footer && footer.trim() !== '') {
        $('#mwsSimFooter').text(footer).show();
      } else {
        $('#mwsSimFooter').hide();
      }

      // 实时生成卡片列表
      var $simBody = $('#mwsSimBody');
      $simBody.empty();

      var accent = $('#mws_color_accent').val() || '#25d366';

      $('#mwsChannelList .mws-channel-item').each(function(i) {
        var badge = $(this).find('.mws-input-badge').val() || (i + 1);
        var label = $(this).find('.mws-input-label').val() || (i18n.channelDefault || '客服通道') + ' ' + (i + 1);
        var number = $(this).find('.mws-input-number').val() || '';
        var sub = $(this).find('.mws-input-sub').val() || '';
        var action = $(this).find('.mws-input-action').val() || 'Chat';
        var isOnline = $(this).find('.mws-input-online').is(':checked');

        var cardHtml =
          '<div class="mws-sim-card">' +
            '<div class="mws-sim-card-left">' +
              '<span class="mws-sim-badge">' + escapeHtml(badge) + '</span>' +
              '<div class="mws-sim-card-info">' +
                '<span class="mws-sim-card-label">' +
                  escapeHtml(label) +
                  (isOnline ? ' <span class="mws-sim-online-dot"></span>' : '') +
                '</span>' +
                (number ? '<span class="mws-sim-phone">' + escapeHtml(number) + '</span>' : '') +
                (sub ? '<span class="mws-sim-sub">' + escapeHtml(sub) + '</span>' : '') +
              '</div>' +
            '</div>' +
            '<span class="mws-sim-btn" style="background:' + accent + '">' + escapeHtml(action) + '</span>' +
          '</div>';

        $simBody.append(cardHtml);
      });

      updatePreviewColors();
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // 监听输入即时更新预览
    $(document).on('input change', '#mws-settings-form input, #mws-settings-form select', function() {
      updateLivePreview();
    });

    // 初始渲染
    updateLivePreview();
  });
})(jQuery);
