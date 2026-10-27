// assets/js/script.js - poore project ka common JavaScript
// Sab kuch ek hi DOMContentLoaded (page tayyar hone par) ke andar hai.

document.addEventListener('DOMContentLoaded', function () {

  // 1) Navbar shadow + Back-to-top button (scroll par)
  const navbar = document.querySelector('.site-navbar');
  const backToTop = document.getElementById('backToTop');
  window.addEventListener('scroll', function () {
    if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 10);
    if (backToTop) backToTop.style.display = window.scrollY > 300 ? 'block' : 'none';
  });
  if (backToTop) {
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // 2) Alerts 5 second baad khud band
  document.querySelectorAll('.auto-dismiss').forEach(function (box) {
    setTimeout(function () {
      if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(box).close();
    }, 5000);
  });

  // 3) Confirm: <a/button data-confirm="Are you sure?">  (achha sa modal, browser ka purana confirm() nahi)
  function showConfirm(message, onOk) {
    let modalEl = document.getElementById('confirmModal');
    if (!modalEl) {
      modalEl = document.createElement('div');
      modalEl.id = 'confirmModal';
      modalEl.className = 'modal fade';
      modalEl.tabIndex = -1;
      modalEl.innerHTML =
        '<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-body text-center p-4">' +
        '<div class="confirm-ico"><i class="bi bi-exclamation-triangle"></i></div>' +
        '<h5 class="mb-2">Are you sure?</h5><p class="text-muted small confirm-msg mb-4"></p>' +
        '<div class="d-flex gap-2 justify-content-center">' +
        '<button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>' +
        '<button type="button" class="btn btn-danger px-4 confirm-ok">Yes, continue</button>' +
        '</div></div></div></div>';
      document.body.appendChild(modalEl);
    }
    modalEl.querySelector('.confirm-msg').textContent = message;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const oldBtn = modalEl.querySelector('.confirm-ok');
    const okBtn = oldBtn.cloneNode(true);                 // purane click listeners hat jayen
    oldBtn.parentNode.replaceChild(okBtn, oldBtn);
    okBtn.addEventListener('click', function () { modal.hide(); onOk(); });
    modal.show();
  }
  function proceed(el) {
    const form = el.closest('form');
    if (el.tagName === 'A') { window.location.href = el.href; }
    else if (form) { form.submit(); }
  }
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      event.preventDefault();
      const message = el.getAttribute('data-confirm');
      if (window.bootstrap) { showConfirm(message, function () { proceed(el); }); }
      else if (confirm(message)) { proceed(el); }          // bootstrap na chale to purana tareeqa
    });
  });

  // 4) Table live search: <input data-table-search="#tableId">
  document.querySelectorAll('[data-table-search]').forEach(function (input) {
    input.addEventListener('keyup', function () {
      const keyword = input.value.toLowerCase();
      document.querySelectorAll(input.getAttribute('data-table-search') + ' tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
      });
    });
  });

  // 5) Password dikhao/chhupao: <button data-toggle-password="#password">
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = document.querySelector(btn.getAttribute('data-toggle-password'));
      const icon = btn.querySelector('i');
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  // 6) Register page: role (Parent / Hospital) ke hisaab se fields, labels aur hints badlo
  const hospitalRadio = document.getElementById('role_hospital');
  if (hospitalRadio) {
    const applyRole = function () {
      const role = hospitalRadio.checked ? 'hospital' : 'parent';
      document.querySelectorAll('[data-show-for]').forEach(function (el) {
        el.classList.toggle('d-none', el.getAttribute('data-show-for') !== role);
      });
      document.querySelectorAll('[data-required-for]').forEach(function (el) {
        el.required = (el.getAttribute('data-required-for') === role);
      });
      document.querySelectorAll('[data-text-parent]').forEach(function (el) {
        el.textContent = el.getAttribute('data-text-' + role);
      });
      document.querySelectorAll('[data-ph-parent]').forEach(function (el) {
        el.setAttribute('placeholder', el.getAttribute('data-ph-' + role));
      });
    };
    document.querySelectorAll('input[name="role"]').forEach(function (r) {
      r.addEventListener('change', applyRole);
    });
    applyRole();
  }

  // 7) Form validation + password match
  document.querySelectorAll('.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      const pass = form.querySelector('#password');
      const confirmBox = form.querySelector('#confirm_password');
      if (pass && confirmBox) {
        confirmBox.setCustomValidity(pass.value !== confirmBox.value ? 'Passwords do not match' : '');
      }
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  // 8) Admin panel: mobile par sidebar kholna/band karna
  const sidebar = document.querySelector('.admin-sidebar');
  const overlay = document.querySelector('.admin-overlay');
  if (sidebar && overlay) {
    const toggleSidebar = function () {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('show');
    };
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
      btn.addEventListener('click', toggleSidebar);
    });
    overlay.addEventListener('click', toggleSidebar);
  }

  // 9) Print button: <button data-print>
  document.querySelectorAll('[data-print]').forEach(function (btn) {
    btn.addEventListener('click', function () { window.print(); });
  });

  // 10) List filter: <input data-filter-items="#list"> aur list ke har item par class "filter-item"
  document.querySelectorAll('[data-filter-items]').forEach(function (input) {
    input.addEventListener('input', function () {
      const keyword = input.value.toLowerCase();
      document.querySelectorAll(input.getAttribute('data-filter-items') + ' .filter-item').forEach(function (item) {
        item.style.display = item.textContent.toLowerCase().includes(keyword) ? '' : 'none';
      });
    });
  });

  // 11) Scroll par elements ka halke se aana: <div data-reveal data-delay="100">
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const revealEls = document.querySelectorAll('[data-reveal]');
  if ('IntersectionObserver' in window && !reduceMotion) {
    const revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(function (el) {
      el.classList.add('reveal');
      el.style.transitionDelay = (el.getAttribute('data-delay') || 0) + 'ms';
      revealObserver.observe(el);
    });
  }

  // 12) Ginti 0 se barhna: <span data-count="25">25</span>  (JS na ho to asli number dikhta rahe)
  function runCounter(el) {
    const target = parseInt(el.getAttribute('data-count'), 10) || 0;
    if (reduceMotion || target === 0) { el.textContent = target; return; }
    const duration = 1200;
    const start = performance.now();
    function step(now) {
      const progress = Math.min((now - start) / duration, 1);
      el.textContent = Math.floor(progress * target);
      if (progress < 1) { requestAnimationFrame(step); } else { el.textContent = target; }
    }
    requestAnimationFrame(step);
  }
  const counters = document.querySelectorAll('[data-count]');
  if ('IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { runCounter(entry.target); counterObserver.unobserve(entry.target); }
      });
    }, { threshold: 0.5 });
    counters.forEach(function (c) { counterObserver.observe(c); });
  }

  // 13) Toast notifications: server ke flash message (class .toast) ko dikhao
  document.querySelectorAll('.toast').forEach(function (el) {
    if (window.bootstrap) { bootstrap.Toast.getOrCreateInstance(el, { delay: 5500 }).show(); }
  });
});

// JavaScript se kahin bhi toast dikhane ke liye:  showToast('success', 'Copied!')
window.showToast = function (type, message) {
  const icons  = { success: ['bi-check-circle-fill', 'Success'], danger: ['bi-x-octagon-fill', 'Error'],
                   warning: ['bi-exclamation-triangle-fill', 'Attention'], info: ['bi-info-circle-fill', 'Info'] };
  const t = icons[type] ? type : 'info';
  let box = document.querySelector('.toast-container.toast-pos');
  if (!box) {
    box = document.createElement('div');
    box.className = 'toast-container toast-pos position-fixed end-0 p-3';
    document.body.appendChild(box);
  }
  const el = document.createElement('div');
  el.className = 'toast toast-vax toast-' + t;
  el.setAttribute('role', 'alert');
  el.innerHTML = '<div class="d-flex align-items-start gap-3 p-3"><i class="bi ' + icons[t][0] + ' toast-ico"></i>' +
    '<div class="flex-fill"><div class="fw-semibold">' + icons[t][1] + '</div><div class="small text-muted"></div></div>' +
    '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="bar"></div>';
  el.querySelector('.text-muted').textContent = message;      // textContent: koi HTML nahi chalta
  box.appendChild(el);
  if (window.bootstrap) {
    const toast = bootstrap.Toast.getOrCreateInstance(el, { delay: 5500 });
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    toast.show();
  }
};
