/**
 * Multi WhatsApp Support - 前端交互核心脚本
 * 纯原生 Vanilla JS 实现，零多余依赖，支持各种主题 Header 自动挂载与平滑弹窗交互
 */
(function() {
  'use strict';

  function initMultiWhatsApp() {
    if (window._mwsInited) return;

    var config = window.mwsConfig || {};
    if (!config.enabled) return;

    var tpl = document.getElementById('mws-popover-template');
    var backdrop = document.getElementById('mwsBackdrop');
    if (!tpl || !backdrop) return;

    // 官方 WhatsApp SVG 图标
    var waSvg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.12.17 1.77 2.71 4.3 3.8 2.53 1.09 2.53.73 2.98.69.46-.05 1.47-.6 1.68-1.18.21-.59.21-1.09.15-1.19-.06-.1-.23-.17-.48-.29z"/></svg>';

    // 实例克隆 Popover
    var popoverFragment = tpl.content.cloneNode(true);
    var popover = popoverFragment.querySelector('.mws-popover');
    if (!popover) return;

    var desktopWrapper = null;
    var desktopBtn = null;
    var mobileBtn = null;
    var floatingWrap = document.getElementById('mwsFloatingWrap');
    var floatingBtn = document.getElementById('mwsFloatingBtn');

    // 移动端浮动球隐藏判断
    if (floatingWrap && config.floatingHideMobile) {
      floatingWrap.classList.add('mws-hide-mobile');
    }

    // 1. 顶部 Header 桌面端挂载
    var shouldMountHeader = config.headerEnabled && (config.displayMode === 'both' || config.displayMode === 'header');

    if (shouldMountHeader) {
      var desktopContainer = document.querySelector(config.headerSelectorDesktop || '.whb-general-header .whb-col-right');

      if (desktopContainer) {
        desktopWrapper = document.createElement('div');
        desktopWrapper.className = 'mws-header-wrapper';
        desktopWrapper.id = 'mwsHeaderWrapper';

        var btnTag = config.headerBtnTag || 'Live Support';
        var btnText = config.headerBtnText || 'WhatsApp';

        desktopWrapper.innerHTML =
          '<button type="button" class="mws-header-btn" id="mwsDesktopBtn" aria-label="WhatsApp Support" aria-haspopup="dialog" aria-expanded="false">' +
            '<span class="mws-header-btn-icon">' +
              '<span class="mws-pulse-dot" aria-hidden="true"></span>' +
              waSvg +
            '</span>' +
            '<span class="mws-header-btn-text">' +
              '<span class="mws-header-btn-tag">' + escapeHtml(btnTag) + '</span>' +
              '<span class="mws-header-btn-title">' +
                escapeHtml(btnText) + ' ' +
                '<svg class="mws-header-btn-caret" width="10" height="10" viewBox="0 0 12 12"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
              '</span>' +
            '</span>' +
          '</button>';

        // 挂载位置计算
        var insertPos = config.headerInsertPosition || 'before_account';
        var accountEl = desktopContainer.querySelector('.wd-header-my-account, .header-account, .my-account-link');

        if (insertPos === 'before_account' && accountEl) {
          desktopContainer.insertBefore(desktopWrapper, accountEl);
        } else if (insertPos === 'prepend') {
          desktopContainer.insertBefore(desktopWrapper, desktopContainer.firstChild);
        } else {
          desktopContainer.appendChild(desktopWrapper);
        }

        desktopBtn = desktopWrapper.querySelector('#mwsDesktopBtn');
      }

      // 移动端 Header 挂载
      var mobileContainer = document.querySelector(config.headerSelectorMobile || '.whb-general-header .whb-mobile-right');
      if (mobileContainer && !document.getElementById('mwsMobileBtn')) {
        mobileBtn = document.createElement('button');
        mobileBtn.type = 'button';
        mobileBtn.className = 'mws-mobile-header-btn';
        mobileBtn.id = 'mwsMobileBtn';
        mobileBtn.setAttribute('aria-label', 'Open WhatsApp Support');
        mobileBtn.setAttribute('aria-haspopup', 'dialog');
        mobileBtn.setAttribute('aria-expanded', 'false');
        mobileBtn.innerHTML = '<span class="mws-pulse-dot" aria-hidden="true"></span>' + waSvg;

        if (mobileContainer.firstChild) {
          mobileContainer.insertBefore(mobileBtn, mobileContainer.firstChild);
        } else {
          mobileContainer.appendChild(mobileBtn);
        }
      }
    }

    // 将 Popover 挂载到最合适的作用域
    if (desktopWrapper && window.innerWidth >= 768) {
      desktopWrapper.appendChild(popover);
    } else {
      document.body.appendChild(popover);
    }

    // 关闭按钮
    var closeBtn = popover.querySelector('#mwsCloseBtn');

    // 状态管理
    var isOpen = false;

    function openModal(triggerElement) {
      if (isOpen) return;
      isOpen = true;

      // 视口检查：若在桌面端且存在 desktopWrapper，则吸附于 header
      if (window.innerWidth >= 768 && desktopWrapper) {
        if (!desktopWrapper.contains(popover)) {
          desktopWrapper.appendChild(popover);
        }
        desktopWrapper.classList.add('is-open');
      } else {
        if (popover.parentNode !== document.body) {
          document.body.appendChild(popover);
        }
      }

      popover.classList.add('is-open');
      backdrop.classList.add('is-active');

      if (desktopBtn) desktopBtn.setAttribute('aria-expanded', 'true');
      if (mobileBtn) mobileBtn.setAttribute('aria-expanded', 'true');
      if (floatingBtn) floatingBtn.setAttribute('aria-expanded', 'true');

      // 聚焦关闭按钮提升无障碍
      if (closeBtn) closeBtn.focus();
    }

    function closeModal() {
      if (!isOpen) return;
      isOpen = false;

      popover.classList.remove('is-open');
      backdrop.classList.remove('is-active');

      if (desktopWrapper) {
        desktopWrapper.classList.remove('is-open');
      }

      if (desktopBtn) desktopBtn.setAttribute('aria-expanded', 'false');
      if (mobileBtn) mobileBtn.setAttribute('aria-expanded', 'false');
      if (floatingBtn) floatingBtn.setAttribute('aria-expanded', 'false');
    }

    function toggleModal(e, triggerElement) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      if (isOpen) {
        closeModal();
      } else {
        openModal(triggerElement);
      }
    }

    // 绑定触发器事件
    if (desktopBtn) {
      desktopBtn.addEventListener('click', function(e) {
        toggleModal(e, desktopBtn);
      });
    }

    if (mobileBtn) {
      mobileBtn.addEventListener('click', function(e) {
        toggleModal(e, mobileBtn);
      });
    }

    if (floatingBtn) {
      floatingBtn.addEventListener('click', function(e) {
        toggleModal(e, floatingBtn);
      });
    }

    // 短代码中的触发按钮
    document.querySelectorAll('.mws-trigger-modal').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        toggleModal(e, btn);
      });
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeModal();
      });
    }

    // 点击遮罩关闭
    backdrop.addEventListener('click', function(e) {
      e.preventDefault();
      closeModal();
    });

    // ESC 键关闭
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' || e.keyCode === 27) {
        if (isOpen) closeModal();
      }
    });

    // 点击弹窗外部关闭
    document.addEventListener('click', function(e) {
      if (!isOpen) return;
      var target = e.target;
      if (
        !popover.contains(target) &&
        (!desktopBtn || !desktopBtn.contains(target)) &&
        (!mobileBtn || !mobileBtn.contains(target)) &&
        (!floatingBtn || !floatingBtn.contains(target)) &&
        !target.closest('.mws-trigger-modal')
      ) {
        closeModal();
      }
    });

    // 窗口尺寸发生变化时自适应挂载点
    var resizeTimer = null;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        if (window.innerWidth >= 768 && desktopWrapper) {
          if (popover.parentNode !== desktopWrapper) {
            desktopWrapper.appendChild(popover);
          }
        } else {
          if (popover.parentNode !== document.body) {
            document.body.appendChild(popover);
          }
        }
      }, 150);
    });

    // 点击通道卡片统计派发自定义事件 (方便 GTM / GA 跟踪转化)
    popover.querySelectorAll('.mws-card').forEach(function(card) {
      card.addEventListener('click', function() {
        var idx = card.getAttribute('data-channel-idx');
        var name = card.getAttribute('data-channel-name');
        try {
          var ev = new CustomEvent('mws:chat_click', {
            bubbles: true,
            detail: {
              channelIndex: idx,
              channelName: name
            }
          });
          document.dispatchEvent(ev);
        } catch(err) {}
      });
    });

    window._mwsInited = true;
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMultiWhatsApp);
  } else {
    initMultiWhatsApp();
  }
})();
