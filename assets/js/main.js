document.addEventListener('DOMContentLoaded', function () {
  // Auto-open a <details> FAQ item when linked via #hash, and scroll to it
  if (location.hash) {
    var targetDetails = document.querySelector(location.hash);
    if (targetDetails && targetDetails.tagName === 'DETAILS') {
      targetDetails.open = true;
      setTimeout(function () { targetDetails.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 50);
    }
  }

  // Live order tracking: poll for status changes so an update made in the
  // admin panel shows up on a customer's open tracking page automatically.
  var orderTrackWrap = document.getElementById('orderTrackWrap');
  if (orderTrackWrap) {
    var trackRef = orderTrackWrap.getAttribute('data-ref');
    var trackEmail = orderTrackWrap.getAttribute('data-email');
    var pillsEl = document.getElementById('orderStatusPills');
    var timelineEl = document.getElementById('orderTimeline');
    var trackBase = window.STRIDECO_BASE || '/';

    function pollOrderStatus() {
      fetch(trackBase + 'order-status.php?ref=' + encodeURIComponent(trackRef) + '&email=' + encodeURIComponent(trackEmail))
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data.ok) return;
          if (pillsEl) {
            pillsEl.innerHTML =
              '<span class="pill pill-' + data.payment_status + '">Payment: ' + data.payment_status + '</span>' +
              '<span class="pill pill-' + data.order_status + '">Status: ' + data.order_status + '</span>';
          }
          if (timelineEl) { timelineEl.innerHTML = data.timeline_html; }
        })
        .catch(function () { /* offline or blip — try again next interval */ });
    }

    setInterval(pollOrderStatus, 20000);
  }

  // Checkout: re-estimate shipping/tax/total as the customer types their
  // delivery city (shipping is zone-based, not a flat fee — see
  // resolve_shipping_fee() in includes/functions.php).
  var checkoutCityInput = document.getElementById('city');
  var checkoutData = document.getElementById('checkoutData');
  if (checkoutCityInput && checkoutData) {
    var apiBase = window.STRIDECO_BASE || '/';
    var shippingEl = document.getElementById('summaryShipping');
    var taxRowEl = document.getElementById('summaryTaxRow');
    var taxEl = document.getElementById('summaryTax');
    var totalEl = document.getElementById('summaryTotal');
    var payButtonEl = document.getElementById('payButton');
    var estimateNoteEl = document.getElementById('shippingEstimateNote');
    var estimateTimer = null;

    function refreshShippingEstimate() {
      var city = checkoutCityInput.value.trim();
      if (!city) return;
      fetch(apiBase + 'shipping-estimate.php?city=' + encodeURIComponent(city))
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data.ok) return;
          if (shippingEl) { shippingEl.textContent = data.shipping_label; }
          if (taxEl && data.tax > 0) { taxEl.textContent = formatNaira(data.tax); }
          if (totalEl) { totalEl.textContent = data.total_label; }
          if (payButtonEl) {
            payButtonEl.textContent = data.total > 0
              ? 'Pay ' + data.total_label + ' with Paystack'
              : 'Place order — fully covered by gift card';
          }
          if (estimateNoteEl && data.zone_name) {
            estimateNoteEl.textContent = 'Delivery fee: ' + data.shipping_label + ' (' + data.zone_name + ')';
          }
          if (taxRowEl) { taxRowEl.style.display = data.tax > 0 ? '' : 'none'; }
        })
        .catch(function () { /* offline or blip — keep the last known estimate */ });
    }

    function formatNaira(amount) {
      return '₦' + amount.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    checkoutCityInput.addEventListener('input', function () {
      clearTimeout(estimateTimer);
      estimateTimer = setTimeout(refreshShippingEstimate, 600);
    });
    checkoutCityInput.addEventListener('blur', refreshShippingEstimate);
  }

  // Checkout: capture a cart snapshot once the customer's email is known
  // (blurred the email field), so an abandoned-cart reminder can be sent if
  // they never finish placing the order. Fire-and-forget — never blocks
  // checkout, and `keepalive` lets it complete even if they navigate away
  // right after (e.g. closing the tab).
  var checkoutEmailInput = document.getElementById('email');
  var checkoutForm = document.querySelector('form[action*="place-order.php"]');
  if (checkoutEmailInput && checkoutForm) {
    var lastCapturedEmail = '';
    checkoutEmailInput.addEventListener('blur', function () {
      var email = checkoutEmailInput.value.trim();
      if (!email || email === lastCapturedEmail || !email.includes('@')) return;
      lastCapturedEmail = email;
      var csrfInput = checkoutForm.querySelector('input[name="csrf_token"]');
      var body = new URLSearchParams();
      body.set('email', email);
      if (csrfInput) { body.set('csrf_token', csrfInput.value); }
      fetch((window.STRIDECO_BASE || '/') + 'capture-abandoned-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
        keepalive: true,
      }).catch(function () { /* best-effort only */ });
    });
  }

  // Homepage hero slider with 3D tilt-on-hover
  var heroSlider = document.getElementById('heroSlider');
  if (heroSlider) {
    var heroSlides = heroSlider.querySelectorAll('.hero-slide');
    var heroDots = heroSlider.querySelectorAll('.hero-dot');
    var heroPrev = document.getElementById('heroSliderPrev');
    var heroNext = document.getElementById('heroSliderNext');
    var heroIndex = 0;
    var heroTimer = null;

    function showHeroSlide(index) {
      heroIndex = (index + heroSlides.length) % heroSlides.length;
      heroSlides.forEach(function (s, i) { s.classList.toggle('active', i === heroIndex); });
      heroDots.forEach(function (d, i) { d.classList.toggle('active', i === heroIndex); });
    }

    function startHeroAuto() {
      stopHeroAuto();
      if (heroSlides.length > 1) {
        heroTimer = setInterval(function () { showHeroSlide(heroIndex + 1); }, 4500);
      }
    }
    function stopHeroAuto() {
      if (heroTimer) { clearInterval(heroTimer); heroTimer = null; }
    }

    if (heroPrev) { heroPrev.addEventListener('click', function () { showHeroSlide(heroIndex - 1); startHeroAuto(); }); }
    if (heroNext) { heroNext.addEventListener('click', function () { showHeroSlide(heroIndex + 1); startHeroAuto(); }); }
    heroDots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        showHeroSlide(parseInt(dot.getAttribute('data-index'), 10));
        startHeroAuto();
      });
    });

    // Subtle parallax pan on the full-bleed backdrop (not a 3D tilt — a large
    // edge-to-edge image rotating in 3D would reveal gaps and look glitchy).
    heroSlider.addEventListener('mouseenter', stopHeroAuto);
    heroSlider.addEventListener('mouseleave', function () {
      startHeroAuto();
      var activeImg = heroSlider.querySelector('.hero-slide.active .hero-slide-bg');
      if (activeImg) { activeImg.style.transform = ''; }
    });
    heroSlider.addEventListener('mousemove', function (e) {
      var activeImg = heroSlider.querySelector('.hero-slide.active .hero-slide-bg');
      if (!activeImg) return;
      var rect = heroSlider.getBoundingClientRect();
      var relX = (e.clientX - rect.left) / rect.width - 0.5;
      var relY = (e.clientY - rect.top) / rect.height - 0.5;
      var panX = relX * -16;
      var panY = relY * -16;
      activeImg.style.transform = 'scale(1.07) translate(' + panX.toFixed(1) + 'px, ' + panY.toFixed(1) + 'px)';
    });

    startHeroAuto();
  }

  // Mobile nav toggle
  var navToggle = document.getElementById('navToggle');
  var mainNav = document.getElementById('mainNav');
  if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () {
      var isOpen = mainNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  // Quantity steppers: any .qty-stepper wraps [data-step="-1|1"] buttons and one input
  document.querySelectorAll('.qty-stepper').forEach(function (stepper) {
    var input = stepper.querySelector('input');
    if (!input) return;
    stepper.querySelectorAll('[data-step]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var step = parseInt(btn.getAttribute('data-step'), 10);
        var min = parseInt(input.getAttribute('min') || '1', 10);
        var max = parseInt(input.getAttribute('max') || '99', 10);
        var next = (parseInt(input.value, 10) || min) + step;
        if (next < min) next = min;
        if (next > max) next = max;
        input.value = next;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
  });

  // Auto-submit cart quantity changes
  document.querySelectorAll('.cart-qty-form input[type="number"]').forEach(function (input) {
    input.addEventListener('change', function () {
      input.form.submit();
    });
  });

  // Toast: auto-dismiss + manual close
  var toast = document.querySelector('.toast');
  if (toast) {
    var dismissToast = function () {
      toast.classList.add('toast-hide');
      setTimeout(function () { toast.remove(); }, 250);
    };
    var autoDismiss = setTimeout(dismissToast, 4500);
    var closeBtn = toast.querySelector('.toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        clearTimeout(autoDismiss);
        dismissToast();
      });
    }
  }

  // Product gallery: click a thumbnail to swap the main image/video
  var mainImage = document.getElementById('mainProductImage');
  var mainVideo = document.getElementById('mainProductVideo');
  var pdZoomBtnEl = document.getElementById('pdZoomBtn');

  function showMainMedia(src, type) {
    if (type === 'video') {
      if (mainVideo) {
        mainVideo.src = src;
        mainVideo.style.display = 'block';
        mainVideo.load();
      }
      if (mainImage) { mainImage.style.display = 'none'; }
      if (pdZoomBtnEl) { pdZoomBtnEl.style.display = 'none'; }
    } else {
      if (mainImage) { mainImage.src = src; mainImage.style.display = 'block'; }
      if (mainVideo) { mainVideo.pause(); mainVideo.removeAttribute('src'); mainVideo.style.display = 'none'; }
      if (pdZoomBtnEl) { pdZoomBtnEl.style.display = ''; }
    }
  }

  document.querySelectorAll('.pd-thumb').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      showMainMedia(thumb.getAttribute('data-src'), thumb.getAttribute('data-type'));
      document.querySelectorAll('.pd-thumb').forEach(function (t) { t.classList.remove('active'); });
      thumb.classList.add('active');
    });
  });

  // Image lightbox / zoom (product detail page) — images only; videos play inline via their own controls
  var lightbox = document.getElementById('lightboxOverlay');
  var lightboxImage = document.getElementById('lightboxImage');
  var lightboxClose = document.getElementById('lightboxClose');
  var lightboxPrev = document.getElementById('lightboxPrev');
  var lightboxNext = document.getElementById('lightboxNext');
  var pdGallery = document.querySelector('.pd-gallery');
  var galleryMedia = window.PRODUCT_GALLERY_IMAGES || [];
  var galleryImageUrls = galleryMedia.filter(function (m) { return m.type !== 'video'; }).map(function (m) { return m.url; });
  var lightboxIndex = 0;

  function showLightboxImage(index) {
    if (!galleryImageUrls.length || !lightboxImage) return;
    lightboxIndex = (index + galleryImageUrls.length) % galleryImageUrls.length;
    lightboxImage.src = galleryImageUrls[lightboxIndex];
  }

  function openLightbox() {
    if (!lightbox || !mainImage || mainImage.style.display === 'none') return;
    var startIndex = galleryImageUrls.indexOf(mainImage.src);
    showLightboxImage(startIndex > -1 ? startIndex : 0);
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (pdGallery) {
    pdGallery.addEventListener('click', function (e) {
      if (mainVideo && mainVideo.style.display !== 'none') return;
      openLightbox();
    });
  }
  if (pdZoomBtnEl) { pdZoomBtnEl.addEventListener('click', function (e) { e.stopPropagation(); openLightbox(); }); }
  if (lightboxClose) { lightboxClose.addEventListener('click', closeLightbox); }
  if (lightboxPrev) { lightboxPrev.addEventListener('click', function () { showLightboxImage(lightboxIndex - 1); }); }
  if (lightboxNext) { lightboxNext.addEventListener('click', function () { showLightboxImage(lightboxIndex + 1); }); }
  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) { closeLightbox(); }
    });
  }
  document.addEventListener('keydown', function (e) {
    if (!lightbox || !lightbox.classList.contains('open')) return;
    if (e.key === 'Escape') { closeLightbox(); }
    if (e.key === 'ArrowLeft') { showLightboxImage(lightboxIndex - 1); }
    if (e.key === 'ArrowRight') { showLightboxImage(lightboxIndex + 1); }
  });

  // Quick view modal
  var qvOverlay = document.getElementById('quickViewOverlay');
  var qvContent = document.getElementById('quickViewContent');
  var qvClose = document.getElementById('quickViewClose');
  var qvBase = window.STRIDECO_BASE || '/';

  function openQuickView(slug) {
    if (!qvOverlay || !qvContent) return;
    qvContent.innerHTML = '<div class="modal-loading">Loading&hellip;</div>';
    qvOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    fetch(qvBase + 'quick-view.php?slug=' + encodeURIComponent(slug))
      .then(function (res) { return res.text(); })
      .then(function (html) { qvContent.innerHTML = html; })
      .catch(function () { qvContent.innerHTML = '<div class="modal-loading">Could not load this shoe. Please try again.</div>'; });
  }

  function closeQuickView() {
    if (!qvOverlay) return;
    qvOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  // Quick view gallery thumbnails are injected dynamically, so use delegation
  if (qvContent) {
    qvContent.addEventListener('click', function (e) {
      var thumb = e.target.closest('.qv-thumb');
      if (!thumb) return;
      var qvMainImage = document.getElementById('qvMainImage');
      var qvMainVideo = document.getElementById('qvMainVideo');
      if (!qvMainImage) return;
      var src = thumb.getAttribute('data-src');
      if (thumb.getAttribute('data-type') === 'video') {
        if (qvMainVideo) { qvMainVideo.src = src; qvMainVideo.style.display = 'block'; qvMainVideo.load(); }
        qvMainImage.style.display = 'none';
      } else {
        qvMainImage.src = src;
        qvMainImage.style.display = 'block';
        if (qvMainVideo) { qvMainVideo.pause(); qvMainVideo.removeAttribute('src'); qvMainVideo.style.display = 'none'; }
      }
      qvContent.querySelectorAll('.qv-thumb').forEach(function (t) { t.classList.remove('active'); });
      thumb.classList.add('active');
    });
  }

  document.querySelectorAll('.quick-view-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openQuickView(btn.getAttribute('data-slug'));
    });
  });
  if (qvClose) { qvClose.addEventListener('click', closeQuickView); }
  if (qvOverlay) {
    qvOverlay.addEventListener('click', function (e) {
      if (e.target === qvOverlay) { closeQuickView(); }
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && qvOverlay && qvOverlay.classList.contains('open')) { closeQuickView(); }
  });

  // Size selector: disable Add to Cart until a size is chosen (product detail page)
  var sizeInputs = document.querySelectorAll('.size-option input');
  var addToCartBtn = document.getElementById('addToCartBtn');
  var addToCartForm = document.getElementById('addToCartForm');
  var stickyBtn = document.getElementById('stickyCartBtn');

  function selectedSizeInput() {
    return document.querySelector('.size-option input:checked');
  }

  if (sizeInputs.length && addToCartBtn) {
    sizeInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        addToCartBtn.removeAttribute('disabled');
        if (stickyBtn) { stickyBtn.textContent = 'Add to Cart · Size ' + input.value; }
      });
    });
  }

  // Sticky mobile bar: scroll to the size picker if nothing's chosen yet,
  // otherwise submit the real add-to-cart form directly.
  if (stickyBtn && addToCartForm) {
    stickyBtn.addEventListener('click', function () {
      if (selectedSizeInput()) {
        if (addToCartForm.requestSubmit) {
          addToCartForm.requestSubmit();
        } else {
          addToCartBtn.click();
        }
      } else {
        addToCartForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  // Color variants (product detail page): picking a swatch swaps the main
  // photo and re-evaluates which sizes are in stock for that color, without
  // a page reload. See includes/functions.php get_product_variants() for
  // why a product with no variants never runs any of this.
  var colorSwatchRow = document.getElementById('colorSwatchRow');
  var variantsData = window.PRODUCT_VARIANTS || [];
  if (colorSwatchRow && variantsData.length) {
    var selectedVariantInput = document.getElementById('selectedVariantId');
    var sizeGrid = document.getElementById('sizeGrid');
    var stockWarningEl = document.getElementById('stockWarning');

    var applyVariant = function (variantId) {
      var variant = null;
      for (var i = 0; i < variantsData.length; i++) {
        if (variantsData[i].id === variantId) { variant = variantsData[i]; break; }
      }
      if (!variant) { return; }

      if (mainImage) { mainImage.src = variant.image; mainImage.style.display = 'block'; }
      if (mainVideo) { mainVideo.pause(); mainVideo.removeAttribute('src'); mainVideo.style.display = 'none'; }
      if (pdZoomBtnEl) { pdZoomBtnEl.style.display = ''; }
      document.querySelectorAll('.pd-thumb').forEach(function (t) { t.classList.remove('active'); });

      var anyInStock = false;
      var totalStock = 0;
      if (sizeGrid) {
        sizeGrid.querySelectorAll('.size-option').forEach(function (opt) {
          var size = opt.getAttribute('data-size');
          var stock = variant.sizes[size] || 0;
          var input = opt.querySelector('input[type="radio"]');
          var disabled = stock <= 0;
          opt.classList.toggle('disabled', disabled);
          if (input) {
            input.disabled = disabled;
            if (disabled && input.checked) { input.checked = false; }
          }
          if (stock > 0) { anyInStock = true; }
          totalStock += stock;
        });
      }

      if (selectedVariantInput) { selectedVariantInput.value = variant.id; }
      if (addToCartBtn) { addToCartBtn.disabled = !anyInStock; }
      if (stickyBtn) { stickyBtn.textContent = 'Add to Cart'; }

      if (stockWarningEl) {
        if (!anyInStock) {
          stockWarningEl.textContent = 'This shoe is currently out of stock in all sizes.';
          stockWarningEl.className = 'form-error';
          stockWarningEl.style.display = '';
        } else if (totalStock <= 8) {
          stockWarningEl.textContent = 'Only ' + totalStock + ' pair' + (totalStock === 1 ? '' : 's') + ' left in stock — order soon!';
          stockWarningEl.className = 'stock-low-note';
          stockWarningEl.style.display = '';
        } else {
          stockWarningEl.style.display = 'none';
        }
      }
    };

    colorSwatchRow.querySelectorAll('input[name="variant_display"]').forEach(function (input) {
      input.addEventListener('change', function () {
        applyVariant(parseInt(input.value, 10));
      });
    });
  }
});
