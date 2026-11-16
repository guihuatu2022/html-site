/* ===================================================================
   "三十而立" 博客 — 全局公共脚本
   所有前端页面 & 后台页面共享此文件
   统一：登录管理 / Toast / Tab 切换 / 抽屉 / 进度条 / 灯箱 / 弹窗
   =================================================================== */
(function(global) {
  'use strict';

  var ADMIN_STORAGE_KEY = 'blog_admin_logged_in';
  var DEFAULT_PASSWORD = 'TheChronicle2024';

  /* -------------------------------------------------------------------
     1. Toast 通知
     ------------------------------------------------------------------- */
  function createToastContainer() {
    var el = document.getElementById('global-toast');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'global-toast';
    el.className = 'toast';
    document.body.appendChild(el);
    return el;
  }

  function showToast(msg, duration) {
    duration = duration || 2000;
    var el = createToastContainer();
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._timeout);
    el._timeout = setTimeout(function() { el.classList.remove('show'); }, duration);
  }

  /* -------------------------------------------------------------------
     2. Tab 切换
     ------------------------------------------------------------------- */
  function switchTab(btn, panelId) {
    var bar = btn.parentElement;
    if (!bar) return;

    bar.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');

    var container = findTabContainer(btn);
    if (!container) return;

    container.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
    var panel = document.getElementById(panelId);
    if (panel) panel.classList.add('active');
  }

  function findTabContainer(el) {
    var p = el.parentElement;
    while (p) {
      if (p.querySelector('.tab-panel')) return p;
      p = p.parentElement;
    }
    return document;
  }

  /* -------------------------------------------------------------------
     3. 批量全选
     ------------------------------------------------------------------- */
  function toggleSelectAll(cb, tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    table.querySelectorAll('tbody input[type="checkbox"]').forEach(function(c) {
      c.checked = cb.checked;
    });
  }

  /* -------------------------------------------------------------------
     4. 管理员登录 / 登出
     ------------------------------------------------------------------- */
  function isAdmin() {
    return document.body.classList.contains('is-admin');
  }

  function setAdmin(state) {
    var menus = document.querySelectorAll('.admin-menu-item');
    if (state) {
      document.body.classList.add('is-admin');
      localStorage.setItem(ADMIN_STORAGE_KEY, '1');
      menus.forEach(function(m) { m.style.opacity = '1'; m.style.maxHeight = '300px'; m.style.transform = 'translateY(0)'; });
    } else {
      document.body.classList.remove('is-admin');
      localStorage.removeItem(ADMIN_STORAGE_KEY);
      menus.forEach(function(m) { m.style.opacity = '0'; m.style.maxHeight = '0'; m.style.transform = 'translateY(10px)'; });
    }
  }

  function restoreAdmin() {
    if (localStorage.getItem(ADMIN_STORAGE_KEY)) {
      setAdmin(true);
    }
  }

  /* -------------------------------------------------------------------
     5. 密码弹窗 (管理员登录)
     ------------------------------------------------------------------- */
  function initPasswordModal() {
    var modal = document.getElementById('password-modal');
    if (!modal) return;

    var input = document.getElementById('password-input');
    var submitBtn = document.getElementById('password-submit');
    var cancelBtn = document.getElementById('password-cancel');

    function submit() {
      if (input && input.value === DEFAULT_PASSWORD) {
        setAdmin(true);
        modal.classList.add('hidden');
        showToast('印泥正确，欢迎主人归来');
      } else {
        showToast('印泥不对，请再试一次');
      }
    }

    if (submitBtn) submitBtn.addEventListener('click', submit);
    if (cancelBtn) cancelBtn.addEventListener('click', function() { modal.classList.add('hidden'); });
    if (input) input.addEventListener('keydown', function(e) { if (e.key === 'Enter') submit(); });

    global.showPasswordModal = function() {
      modal.classList.remove('hidden');
      if (input) { input.value = ''; setTimeout(function() { input.focus(); }, 100); }
    };
  }

  /* -------------------------------------------------------------------
     6. 加密内容弹窗
     ------------------------------------------------------------------- */
  function initEncryptModal() {
    var modal = document.getElementById('encrypt-password-modal');
    if (!modal) return;

    var input = document.getElementById('encrypt-input');
    var submitBtn = document.getElementById('encrypt-submit');
    var cancelBtn = document.getElementById('encrypt-cancel');
    var titleEl = document.getElementById('encrypt-title');
    var currentItem = null;
    var onSuccess = null;

    if (submitBtn) {
      submitBtn.addEventListener('click', function() {
        if (input && input.value && currentItem) {
          currentItem.classList.add('encrypted-unlocked');
          modal.classList.add('hidden');
          if (onSuccess) onSuccess(currentItem);
          currentItem = null;
          onSuccess = null;
        }
      });
    }

    if (cancelBtn) {
      cancelBtn.addEventListener('click', function() {
        modal.classList.add('hidden');
        currentItem = null;
        onSuccess = null;
      });
    }

    if (input) {
      input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && submitBtn) submitBtn.click();
      });
    }

    global.openEncryptModal = function(item, title, callback) {
      currentItem = item;
      onSuccess = callback || null;
      if (titleEl) titleEl.textContent = title || '加密内容';
      modal.classList.remove('hidden');
      if (input) { input.value = ''; setTimeout(function() { input.focus(); }, 100); }
    };

    document.querySelectorAll('.entry-encrypted').forEach(function(el) {
      if (el.dataset.encryptBound === '1') return;
      el.dataset.encryptBound = '1';
      el.addEventListener('click', function(e) {
        if (el.classList.contains('encrypted-unlocked')) return;
        e.preventDefault();
        var titleEl = el.querySelector('.entry-title-text, .file-name');
        var title = titleEl ? titleEl.textContent.trim() : '加密内容';
        global.openEncryptModal(el, title, function(target) {
          var link = target.querySelector('a');
          if (link && link.href) global.location.href = link.href;
        });
      });
    });
  }

  /* -------------------------------------------------------------------
     7. 相册解锁弹窗
     ------------------------------------------------------------------- */
  function initAlbumUnlockModal() {
    var modal = document.getElementById('album-unlock-modal');
    if (!modal) return;

    var titleEl = document.getElementById('album-unlock-title');
    var input = document.getElementById('album-unlock-input');

    global.openAlbumUnlock = function(title) {
      if (titleEl) titleEl.textContent = '解锁"《' + title + '》"';
      modal.classList.remove('hidden');
      if (input) { input.value = ''; setTimeout(function() { input.focus(); }, 100); }
    };

    global.closeAlbumUnlock = function() {
      modal.classList.add('hidden');
    };

    global.submitAlbumUnlock = function() {
      if (input && input.value) {
        closeAlbumUnlock();
        global.location.href = '相册详情页.html';
      }
    };
  }

  /* -------------------------------------------------------------------
     8. 阅读进度条
     ------------------------------------------------------------------- */
  function initReadingProgress() {
    var bar = document.querySelector('.reading-progress');
    if (!bar) return;
    global.addEventListener('scroll', function() {
      var scrollTop = global.scrollY || document.documentElement.scrollTop;
      var docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      var pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      bar.style.width = Math.min(pct, 100) + '%';
    }, { passive: true });
  }

  /* -------------------------------------------------------------------
     9. 回到顶部
     ------------------------------------------------------------------- */
  function initBackToTop() {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;
    var ticking = false;
    global.addEventListener('scroll', function() {
      if (!ticking) {
        requestAnimationFrame(function() {
          btn.classList.toggle('visible', global.scrollY > 500);
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
    btn.addEventListener('click', function() {
      global.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* -------------------------------------------------------------------
     10. 移动端抽屉
     ------------------------------------------------------------------- */
  function initMobileDrawer() {
    var hamburger = document.querySelector('.hamburger-btn');
    var nav = document.querySelector('.sidebar nav');
    var overlay = document.querySelector('.sidebar-overlay');
    if (!hamburger || !nav || !overlay) return;

    var closeBtn = nav.querySelector('.close-drawer');

    function open() { nav.classList.add('open'); overlay.classList.add('show'); }
    function close() { nav.classList.remove('open'); overlay.classList.remove('show'); }

    hamburger.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', close);

    nav.addEventListener('click', function(e) {
      var link = e.target.closest('a');
      if (!link || !link.href || link.getAttribute('href') === '#') return;
      e.preventDefault();
      close();
      setTimeout(function() { global.location.href = link.getAttribute('href'); }, 250);
    });
  }

  /* -------------------------------------------------------------------
     11. 灯箱 (相册浏览)
     ------------------------------------------------------------------- */
  function initLightbox() {
    var lightbox = document.getElementById('lightbox');
    if (!lightbox) return;

    var currentIndex = 0;
    var photos = [];

    function update() {
      var photo = photos[currentIndex];
      if (!photo) return;
      var img = lightbox.querySelector('.lightbox-img-area img');
      var titleEl = lightbox.querySelector('.lightbox-title');
      var descEl = lightbox.querySelector('.lightbox-desc');
      var exifEl = lightbox.querySelector('.lightbox-exif');
      var counterEl = lightbox.querySelector('.lightbox-counter');

      if (img) {
        img.src = photo.getAttribute('data-full') || (photo.querySelector('img') ? photo.querySelector('img').src : '');
      }
      if (titleEl) titleEl.textContent = photo.getAttribute('data-title') || '';
      if (descEl) descEl.textContent = photo.getAttribute('data-desc') || '';

      if (exifEl) {
        var camera = photo.getAttribute('data-camera') || '';
        var lens = photo.getAttribute('data-lens') || '';
        var aperture = photo.getAttribute('data-aperture') || '';
        var shutter = photo.getAttribute('data-shutter') || '';
        var iso = photo.getAttribute('data-iso') || '';
        var focal = photo.getAttribute('data-focal') || '';

        var hasExif = camera || lens || aperture || shutter || iso || focal;
        if (hasExif) {
          var html = '';
          if (camera) html += '<div class="lightbox-exif-item"><span class="lightbox-exif-label">相机</span><span class="lightbox-exif-value">' + camera + '</span></div>';
          if (lens) html += '<div class="lightbox-exif-item"><span class="lightbox-exif-label">镜头</span><span class="lightbox-exif-value">' + lens + '</span></div>';
          if (aperture) html += '<div class="lightbox-exif-item"><span class="lightbox-exif-label">光圈</span><span class="lightbox-exif-value">' + aperture + '</span></div>';
          if (shutter) html += '<div class="lightbox-exif-item"><span class="lightbox-exif-label">快门</span><span class="lightbox-exif-value">' + shutter + '</span></div>';
          if (iso) html += '<div class="lightbox-exif-item"><span class="lightbox-exif-label">ISO</span><span class="lightbox-exif-value">' + iso + '</span></div>';
          if (focal) html += '<div class="lightbox-exif-item"><span class="lightbox-exif-label">焦距</span><span class="lightbox-exif-value">' + focal + '</span></div>';
          exifEl.innerHTML = html;
          exifEl.style.display = 'grid';
        } else {
          exifEl.style.display = 'none';
        }
      }

      if (counterEl) {
        counterEl.textContent = (currentIndex + 1) + ' / ' + photos.length;
      }
    }

    document.addEventListener('keydown', function(e) {
      if (lightbox.classList.contains('hidden')) return;
      if (e.key === 'Escape') { closeLightbox(); return; }
      if (e.key === 'ArrowLeft') { e.preventDefault(); if (photos.length) { currentIndex = (currentIndex - 1 + photos.length) % photos.length; update(); } }
      if (e.key === 'ArrowRight') { e.preventDefault(); if (photos.length) { currentIndex = (currentIndex + 1) % photos.length; update(); } }
    });

    global.openLightbox = function(photoEl) {
      var allPhotos = document.querySelectorAll('.single-photo');
      photos = Array.from(allPhotos);
      currentIndex = photos.indexOf(photoEl);
      if (currentIndex < 0) currentIndex = 0;
      update();
      lightbox.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    };

    global.closeLightbox = function() {
      lightbox.classList.add('hidden');
      document.body.style.overflow = '';
    };

    global.lightboxPrev = function() {
      if (!photos.length) return;
      currentIndex = (currentIndex - 1 + photos.length) % photos.length;
      update();
    };

    global.lightboxNext = function() {
      if (!photos.length) return;
      currentIndex = (currentIndex + 1) % photos.length;
      update();
    };
  }

  /* -------------------------------------------------------------------
     12. 番茄钟
     ------------------------------------------------------------------- */
  function initPomodoro() {
    var overlay = document.getElementById('pomodoro-overlay');
    if (!overlay) return;
    var timerEl = overlay.querySelector('.pomodoro-timer');
    var startBtn = overlay.querySelector('.pomodoro-btn:first-of-type');
    var resetBtn = overlay.querySelector('.pomodoro-btn:last-of-type');
    var exitBtn = overlay.querySelector('.pomodoro-exit');
    var interval = null;
    var seconds = 25 * 60;

    function formatTime(sec) {
      var m = Math.floor(sec / 60);
      var s = sec % 60;
      return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function updateDisplay() {
      if (timerEl) timerEl.textContent = formatTime(seconds);
    }

    global.startPomodoro = function() {
      if (interval) return;
      interval = setInterval(function() {
        if (seconds > 0) { seconds--; updateDisplay(); }
        else { clearInterval(interval); interval = null; }
      }, 1000);
    };

    global.resetPomodoro = function() {
      clearInterval(interval);
      interval = null;
      seconds = 25 * 60;
      updateDisplay();
    };

    updateDisplay();

    if (startBtn) startBtn.addEventListener('click', global.startPomodoro);
    if (resetBtn) resetBtn.addEventListener('click', global.resetPomodoro);
    if (exitBtn) {
      exitBtn.addEventListener('click', function() {
        overlay.classList.add('hidden');
        global.resetPomodoro();
      });
    }
  }

  /* -------------------------------------------------------------------
     13. 文件预览切换 (网盘详情页)
     ------------------------------------------------------------------- */
  global.switchFilePreview = function(btn, type) {
    var bar = btn.parentElement;
    if (bar) bar.querySelectorAll('.file-type-tab').forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');
    var area = document.getElementById('preview-area');
    if (!area) return;
    if (type === 'image') {
      area.innerHTML = '<img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800" alt="preview" style="max-width:100%;max-height:450px;object-fit:contain">';
    } else if (type === 'video') {
      area.innerHTML = '<video controls style="max-width:100%;max-height:450px"><source src="#" type="video/mp4">您的浏览器不支持视频预览</video>';
    } else if (type === 'pdf') {
      area.innerHTML = '<div style="text-align:center;padding:2rem"><i class="ph ph-file-text text-5xl text-[#bfa57d] block mb-3"></i><p class="font-hand text-[#999]">PDF 文件建议下载后查看</p><p class="font-serif text-xs text-[#ccc] mt-2">在线预览依赖浏览器支持</p></div>';
    } else {
      area.innerHTML = '<div><i class="ph ph-file-zip text-6xl text-[#bfa57d] block mb-4"></i><p class="font-hand text-[#999]">压缩文件，无法在线预览</p><p class="font-serif text-xs text-[#ccc] mt-2">请下载后解压查看</p></div>';
    }
  };

  /* -------------------------------------------------------------------
     14. 四象限待办
     ------------------------------------------------------------------- */
  global.switchMobileQuadrant = function(q, el) {
    document.querySelectorAll('.mq-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
    document.querySelectorAll('.quadrant-cell').forEach(function(c) {
      c.classList.toggle('active-mobile', c.classList.contains('q' + q));
    });
  };

  global.addTodo = function(btn) {
    var cell = btn.closest('.quadrant-cell');
    if (!cell) return;
    var list = cell.querySelector('.todo-list');
    if (!list) return;
    var item = document.createElement('div');
    item.className = 'todo-item';
    item.innerHTML = '<div class="todo-check" onclick="this.parentElement.classList.toggle(\'done\')"></div><span class="todo-text" contenteditable="true">新事项</span><button class="todo-delete" onclick="this.parentElement.remove()"><i class="ph ph-x"></i></button>';
    list.appendChild(item);
    item.querySelector('.todo-text').focus();
    global.updateQuadrantCounts();
  };

  global.updateQuadrantCounts = function() {
    document.querySelectorAll('.quadrant-cell').forEach(function(cell) {
      var total = cell.querySelectorAll('.todo-item').length;
      var done = cell.querySelectorAll('.todo-item.done').length;
      var countEl = cell.querySelector('.q-count');
      var barEl = cell.querySelector('.q-bar-fill');
      if (countEl) countEl.textContent = done + '/' + total;
      if (barEl) barEl.style.width = total > 0 ? (done / total * 100) + '%' : '0%';
    });
  };

  /* -------------------------------------------------------------------
     15. 分类筛选 — 通用
     ------------------------------------------------------------------- */
  global.filterByCategory = function(category, containerSelector, itemSelector) {
    var container = containerSelector ? document.querySelector(containerSelector) : document;
    var links = container.querySelectorAll('.index-link[data-filter]');
    links.forEach(function(l) {
      l.classList.toggle('active', l.getAttribute('data-filter') === category);
    });
    var items = document.querySelectorAll(itemSelector || '.filterable-item');
    items.forEach(function(item) {
      if (category === 'all' || item.getAttribute('data-category') === category) {
        item.classList.remove('filtered-out');
      } else {
        item.classList.add('filtered-out');
      }
    });
  };

  /* -------------------------------------------------------------------
     16. 中国农历模块 (Lunar Calendar)
     数据覆盖 1900-2100，包含：公历→农历转换、年份中文数字、干支纪年
     ------------------------------------------------------------------- */
  var Lunar = (function() {
    var lunarInfo = [
      0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,
      0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,
      0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,
      0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,
      0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,
      0x06ca0,0x0b550,0x15355,0x04da0,0x0a5b0,0x14573,0x052b0,0x0a9a8,0x0e950,0x06aa0,
      0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,
      0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b6a0,0x195a6,
      0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,
      0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,
      0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,
      0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,
      0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,
      0x05aa0,0x076a3,0x096d0,0x04afb,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,
      0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0,
      0x14b63,0x09370,0x049f8,0x04970,0x064b0,0x168a6,0x0ea50,0x06aa0,0x1a6c4,0x0aae0,
      0x092e0,0x0d2e3,0x0c960,0x0d557,0x0d4a0,0x0da50,0x05d55,0x056a0,0x0a6d0,0x055d4,
      0x052d0,0x0a9b8,0x0a950,0x0b4a0,0x0b6a6,0x0ad50,0x055a0,0x0aba4,0x0a5b0,0x052b0,
      0x0b273,0x06930,0x07337,0x06aa0,0x0ad50,0x14b55,0x04b60,0x0a570,0x054e4,0x0d160,
      0x0e968,0x0d520,0x0daa0,0x16aa6,0x056d0,0x04ae0,0x0a9d4,0x0a4d0,0x0d150,0x0f252,
      0x0d520
    ];

    var Gan = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    var Zhi = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    var Animals = ['鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'];
    var lunarMonthsCn = ['正','二','三','四','五','六','七','八','九','十','冬','腊'];
    var lunarDaysCn = [
      '','初一','初二','初三','初四','初五','初六','初七','初八','初九','初十',
      '十一','十二','十三','十四','十五','十六','十七','十八','十九','二十',
      '廿一','廿二','廿三','廿四','廿五','廿六','廿七','廿八','廿九','三十'
    ];
    var numCn = ['零','一','二','三','四','五','六','七','八','九'];

    // 公历日期偏移量（以 1900-01-01 为基准计算天数）
    function solarDayOffset(y, m, d) {
      var totalDays = 0;
      for (var i = 1900; i < y; i++) {
        totalDays += isSolarLeap(i) ? 366 : 365;
      }
      for (var i = 1; i < m; i++) {
        totalDays += solarMonthDays(y, i);
      }
      return totalDays + d - 1;
    }

    function isSolarLeap(y) {
      return (y % 4 === 0 && y % 100 !== 0) || (y % 400 === 0);
    }

    function solarMonthDays(y, m) {
      if (m === 2) return isSolarLeap(y) ? 29 : 28;
      return [31,0,31,30,31,30,31,31,30,31,30,31][m - 1];
    }

    // 获取农历年的月数（含闰月）
    function lunarMonths(y) {
      var info = lunarInfo[y - 1900];
      var leap = info & 0xf;
      return leap === 0 ? 12 : 13;
    }

    // 获取农历月的天数
    function lunarMonthDays(y, m) {
      var info = lunarInfo[y - 1900];
      // 月数据从 bit 4 开始
      return (info & (0x10000 >> m)) ? 30 : 29;
    }

    // 是否闰月
    function isLeapMonth(y, m) {
      var info = lunarInfo[y - 1900];
      var leap = info & 0xf;
      return leap !== 0 && m > leap && lunarMonthIndex(y, m) === leap;
    }

    // 农历月索引（正月初一=1，含闰月但不重复编号）
    function lunarMonthIndex(y, m) {
      var info = lunarInfo[y - 1900];
      var leap = info & 0xf;
      if (leap === 0) return m;
      if (m <= leap) return m;
      return m - 1;
    }

    // 核心：公历→农历转换
    function solarToLunar(solarYear, solarMonth, solarDay) {
      var offset = solarDayOffset(solarYear, solarMonth, solarDay);
      var ly = 1900, lm = 1, ld = 1;

      // 减去 1900-01-31（农历正月初一）之前的天数
      // 1900年正月初一 = 1900-01-31, offset 从 1月1日起
      offset -= 30; // 公历 1月有31天 → 1月31日是第30个偏移

      // 循环走到目标农历年
      while (ly < 2101 && offset >= lunarYearDays(ly)) {
        offset -= lunarYearDays(ly);
        ly++;
      }

      // 走到目标农历月
      var months = lunarMonths(ly);
      var info = lunarInfo[ly - 1900];
      var leap = info & 0xf;

      for (var i = 1; i <= months; i++) {
        var monthDays = lunarMonthDays(ly, i);
        if (offset < monthDays) {
          lm = i;
          ld = offset + 1;
          break;
        }
        offset -= monthDays;
      }

      return {
        year: ly,
        month: lm,
        day: ld,
        isLeap: leap !== 0 && lm > leap && (lm - 1 === leap || (lm === leap + 1 && offset < lunarMonthDays(ly, leap + 1)))
      };
    }

    // 农历年总天数
    function lunarYearDays(y) {
      var info = lunarInfo[y - 1900];
      var sum = 0;
      var months = lunarMonths(y);
      for (var i = 1; i <= months; i++) {
        sum += lunarMonthDays(y, i);
      }
      return sum;
    }

    // 年份→中文数字（如 2024 → 二零二四）
    function yearToChinese(y) {
      var s = String(y);
      var r = '';
      for (var i = 0; i < s.length; i++) {
        r += numCn[parseInt(s[i])];
      }
      return r;
    }

    // 干支纪年
    function sexagenary(y) {
      return Gan[(y - 4) % 10] + Zhi[(y - 4) % 12];
    }

    // 农历月→中文
    function monthToChinese(m, isLeap) {
      return (isLeap ? '闰' : '') + lunarMonthsCn[m - 1] + '月';
    }

    // 农历日→中文
    function dayToChinese(d) {
      return lunarDaysCn[d];
    }

    return {
      solarToLunar: solarToLunar,
      yearToChinese: yearToChinese,
      sexagenary: sexagenary,
      monthToChinese: monthToChinese,
      dayToChinese: dayToChinese,
      formatDate: function(solarYear, solarMonth, solarDay) {
        var lunar = solarToLunar(solarYear, solarMonth, solarDay);
        return {
          yearCn: yearToChinese(lunar.year) + '年',
          ganzhi: sexagenary(lunar.year) + '年',
          monthCn: monthToChinese(lunar.month, lunar.isLeap),
          dayCn: dayToChinese(lunar.day)
        };
      }
    };
  })();

  function initLunarDate() {
    var el = document.getElementById('lunar-date');
    if (!el) return;
    var now = new Date();
    var lunar = Lunar.formatDate(now.getFullYear(), now.getMonth() + 1, now.getDate());
    el.innerHTML =
      '<h2 class="text-3xl font-bold text-[#2c2c2c] mb-1 font-serif">' + lunar.yearCn + '</h2>' +
      '<p class="text-[#8b3a3a] text-sm tracking-widest font-serif">农历 ' + lunar.ganzhi + ' ' + lunar.monthCn + lunar.dayCn + '</p>';
  }

  /* -------------------------------------------------------------------
     17. 初始化
     ------------------------------------------------------------------- */
  function init() {
    restoreAdmin();
    initReadingProgress();
    initBackToTop();
    initMobileDrawer();
    initLightbox();
    initPasswordModal();
    initEncryptModal();
    initAlbumUnlockModal();
    initPomodoro();
    initLunarDate();

    global.showToast = showToast;
    global.switchTab = switchTab;
    global.toggleSelectAll = toggleSelectAll;
    global.Blog = {
      isAdmin: isAdmin,
      setAdmin: setAdmin,
      showToast: showToast
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
