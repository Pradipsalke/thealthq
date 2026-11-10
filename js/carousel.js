/**
 * The ALT HQ - Carousel & Video Lightbox Engine
 * Infinite Auto-play with Persistent Dot Controls
 */

(function () {
  'use strict';

  window.altCarousels = [];

  class AltCarousel {
    constructor(element, options = {}) {
      this.wrapper = element;
      this.viewport = element.querySelector('.alt-carousel-viewport');
      this.track = element.querySelector('.alt-carousel-track');
      this.dotsContainer = element.querySelector('.alt-carousel-dots');

      this.currentIndex = 0;
      this.isDragging = false;
      this.startPos = 0;
      this.currentTranslate = 0;
      this.prevTranslate = 0;
      this.dragDistance = 0;

      // Autoplay: 4 seconds interval
      this.autoplayDelay = options.autoplayDelay || 4000;
      this.autoplayTimer = null;
      this.isPaused = false;

      this.init();
      window.altCarousels.push(this);
    }

    init() {
      if (!this.track) return;

      // Filter out any clones to get true original slides
      this.originalSlides = Array.from(this.track.children).filter(
        (el) => !el.classList.contains('is-clone')
      );
      this.originalCount = this.originalSlides.length;
      if (this.originalCount === 0) return;

      this.setupClones();
      this.bindEvents();
      this.buildDots();
      this.goToSlide(0, false);
      this.startAutoplay();

      window.addEventListener('resize', () => {
        this.setupClones();
        this.buildDots();
        this.goToSlide(this.currentIndex, false);
        this.restartAutoplay();
      });
    }

    getVisibleCount() {
      const w = window.innerWidth;
      if (w <= 640) return 1;
      if (w <= 960) return 2;
      return 3;
    }

    setupClones() {
      // Remove any existing clones
      this.track.querySelectorAll('.is-clone').forEach((el) => el.remove());

      if (this.originalCount <= 1) {
        this.slides = Array.from(this.track.children);
        return;
      }

      // Clone sufficient slides to allow continuous seamless looping
      const visible = this.getVisibleCount();
      const cloneCount = Math.max(visible + 1, 3);
      for (let i = 0; i < cloneCount; i++) {
        const srcSlide = this.originalSlides[i % this.originalCount];
        const clone = srcSlide.cloneNode(true);
        clone.classList.add('is-clone');
        clone.setAttribute('aria-hidden', 'true');
        this.track.appendChild(clone);
      }

      this.slides = Array.from(this.track.children);
    }

    buildDots() {
      if (!this.dotsContainer) return;
      this.dotsContainer.innerHTML = '';

      if (this.originalCount <= 1) {
        this.dotsContainer.style.display = 'none';
        return;
      }

      this.dotsContainer.style.display = 'flex';

      // Build one dot per original card
      for (let i = 0; i < this.originalCount; i++) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className =
          'alt-carousel-dot' + (i === this.currentIndex % this.originalCount ? ' active' : '');
        dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
        dot.addEventListener('click', (e) => {
          e.preventDefault();
          this.goToSlide(i);
          this.restartAutoplay();
        });
        this.dotsContainer.appendChild(dot);
      }
    }

    updateDots() {
      if (!this.dotsContainer) return;
      const activeIdx = this.currentIndex % this.originalCount;
      const dots = this.dotsContainer.querySelectorAll('.alt-carousel-dot');
      dots.forEach((dot, idx) => {
        dot.classList.toggle('active', idx === activeIdx);
      });
    }

    getSlideWidth() {
      if (!this.slides[0]) return 0;
      const slide = this.slides[0];
      const style = window.getComputedStyle(this.track);
      const gap = parseFloat(style.gap) || 24;
      return slide.getBoundingClientRect().width + gap;
    }

    goToSlide(index, animate = true) {
      this.currentIndex = index;
      const slideWidth = this.getSlideWidth();
      this.currentTranslate = -this.currentIndex * slideWidth;
      this.prevTranslate = this.currentTranslate;

      if (!animate) {
        this.track.style.transition = 'none';
      } else {
        this.track.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
      }

      this.track.style.transform = `translateX(${this.currentTranslate}px)`;
      this.updateDots();
    }

    next() {
      if (this.originalCount <= 1) return;
      this.goToSlide(this.currentIndex + 1);
    }

    prev() {
      if (this.originalCount <= 1) return;
      if (this.currentIndex <= 0) {
        // Jump seamlessly to clone and slide backward
        this.goToSlide(this.originalCount, false);
        void this.track.offsetWidth; // force reflow
        this.goToSlide(this.originalCount - 1, true);
      } else {
        this.goToSlide(this.currentIndex - 1);
      }
    }

    bindEvents() {
      // Seamless loop reset when transition finishes at clone
      this.track.addEventListener('transitionend', () => {
        if (this.currentIndex >= this.originalCount) {
          const remainder = this.currentIndex % this.originalCount;
          this.goToSlide(remainder, false);
        }
      });

      // Pause autoplay on mouse hover, resume on mouse leave
      this.wrapper.addEventListener('mouseenter', () => {
        this.isPaused = true;
      });
      this.wrapper.addEventListener('mouseleave', () => {
        this.isPaused = false;
      });

      // Touch & Mouse Dragging
      this.track.addEventListener('touchstart', this.touchStart.bind(this), { passive: true });
      this.track.addEventListener('touchmove', this.touchMove.bind(this), { passive: false });
      this.track.addEventListener('touchend', this.touchEnd.bind(this));

      this.track.addEventListener('mousedown', this.dragStart.bind(this));
      window.addEventListener('mousemove', this.dragMove.bind(this));
      window.addEventListener('mouseup', this.dragEnd.bind(this));

      // Prevent link clicks during drag gesture
      this.track.addEventListener(
        'click',
        (e) => {
          if (this.dragDistance && Math.abs(this.dragDistance) > 10) {
            e.preventDefault();
            e.stopPropagation();
          }
        },
        true
      );
    }

    startAutoplay() {
      this.stopAutoplay();
      if (this.originalCount <= 1) return;

      this.autoplayTimer = setInterval(() => {
        if (this.isPaused || this.isDragging) return;
        this.next();
      }, this.autoplayDelay);
    }

    stopAutoplay() {
      if (this.autoplayTimer) {
        clearInterval(this.autoplayTimer);
        this.autoplayTimer = null;
      }
    }

    restartAutoplay() {
      this.stopAutoplay();
      this.startAutoplay();
    }

    dragStart(e) {
      if (e.button !== 0) return;
      this.stopAutoplay();
      this.isDragging = true;
      this.startPos = e.pageX;
      this.dragDistance = 0;
      this.track.classList.add('is-dragging');
    }

    dragMove(e) {
      if (!this.isDragging) return;
      const currentPosition = e.pageX;
      this.dragDistance = currentPosition - this.startPos;
      const translate = this.prevTranslate + this.dragDistance;
      this.track.style.transform = `translateX(${translate}px)`;
    }

    dragEnd() {
      if (!this.isDragging) return;
      this.isDragging = false;
      this.track.classList.remove('is-dragging');

      const movedBy = this.dragDistance || 0;
      const threshold = 50;

      if (movedBy < -threshold) {
        this.next();
      } else if (movedBy > threshold) {
        this.prev();
      } else {
        this.goToSlide(this.currentIndex);
      }

      this.startAutoplay();
    }

    touchStart(e) {
      this.stopAutoplay();
      this.isDragging = true;
      this.startPos = e.touches[0].clientX;
      this.dragDistance = 0;
      this.track.classList.add('is-dragging');
    }

    touchMove(e) {
      if (!this.isDragging) return;
      const currentPosition = e.touches[0].clientX;
      this.dragDistance = currentPosition - this.startPos;

      if (Math.abs(this.dragDistance) > 10 && e.cancelable) {
        e.preventDefault();
      }
      const translate = this.prevTranslate + this.dragDistance;
      this.track.style.transform = `translateX(${translate}px)`;
    }

    touchEnd() {
      this.dragEnd();
    }
  }

  // Video Modal Lightbox Engine
  const VideoLightbox = {
    init() {
      this.createModal();
      this.bindTriggers();
    },

    createModal() {
      if (document.getElementById('alt-video-lightbox')) return;

      const modal = document.createElement('div');
      modal.id = 'alt-video-lightbox';
      modal.className = 'alt-video-modal';
      modal.innerHTML = `
        <div class="alt-video-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="alt-video-modal-title">
          <button type="button" class="alt-video-modal-close" aria-label="Close video player">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
          <div class="alt-video-modal-content" id="alt-video-player-container"></div>
          <div class="alt-video-modal-footer">
            <h4 class="alt-video-modal-title" id="alt-video-modal-title"></h4>
            <a href="#" target="_blank" rel="noopener" id="alt-video-external-link" style="color: #FFD200; font-size: 14px; font-weight: 600; text-decoration: none; display: none;">Open link ↗</a>
          </div>
        </div>
      `;
      document.body.appendChild(modal);

      this.modal = modal;
      this.container = document.getElementById('alt-video-player-container');
      this.titleEl = document.getElementById('alt-video-modal-title');
      this.linkEl = document.getElementById('alt-video-external-link');
      this.closeBtn = modal.querySelector('.alt-video-modal-close');

      this.closeBtn.addEventListener('click', () => this.close());
      modal.addEventListener('click', (e) => {
        if (e.target === modal) this.close();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.modal.classList.contains('active')) {
          this.close();
        }
      });
    },

    bindTriggers() {
      document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-video-modal]');
        if (!trigger) return;

        e.preventDefault();
        const type = trigger.dataset.videoType || 'youtube';
        const url = trigger.dataset.videoPath || trigger.getAttribute('href');
        const title = trigger.dataset.videoTitle || trigger.querySelector('h4')?.textContent || 'Video';

        this.open(type, url, title);
      });
    },

    open(type, url, title) {
      if (!this.modal) this.createModal();

      // Pause all background carousels
      window.altCarousels?.forEach((c) => c.stopAutoplay());

      this.titleEl.textContent = title;
      this.container.innerHTML = '';

      if (type === 'upload' || url.match(/\.(mp4|webm|ogg|mov)$/i)) {
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.autoplay = true;
        video.playsInline = true;
        this.container.appendChild(video);
        this.linkEl.style.display = 'none';
      } else {
        const ytId = this.extractYouTubeId(url);
        if (ytId) {
          const iframe = document.createElement('iframe');
          iframe.src = `https://www.youtube-nocookie.com/embed/${ytId}?autoplay=1&rel=0`;
          iframe.allow =
            'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
          iframe.allowFullscreen = true;
          this.container.appendChild(iframe);
          this.linkEl.href = url;
          this.linkEl.textContent = 'Watch on YouTube ↗';
          this.linkEl.style.display = 'inline-flex';
        } else {
          window.open(url, '_blank', 'noopener');
          window.altCarousels?.forEach((c) => c.startAutoplay());
          return;
        }
      }

      this.modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    },

    extractYouTubeId(url) {
      if (!url) return null;
      const match = url.match(
        /(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/
      );
      return match ? match[1] : null;
    },

    close() {
      if (!this.modal) return;
      this.modal.classList.remove('active');
      document.body.style.overflow = '';
      this.container.innerHTML = '';

      // Resume all carousels
      window.altCarousels?.forEach((c) => c.startAutoplay());
    }
  };

  // Dynamic Content Loader for Frontend (ensures latest DB content on both index.php and index.html)
  const DynamicLoader = {
    async syncFromAPI(carouselEl, type) {
      const track = carouselEl.querySelector('.alt-carousel-track');
      if (!track) return;

      const endpoint = type === 'insights' ? 'api/get_insights.php' : 'api/get_videos.php';

      try {
        const res = await fetch(`${endpoint}?t=${Date.now()}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();

        if (json.status === 'success' && Array.isArray(json.data) && json.data.length > 0) {
          // If content came from API, populate it dynamically
          track.innerHTML = json.data
            .map((item) =>
              type === 'insights' ? this.renderInsightCard(item) : this.renderVideoCard(item)
            )
            .join('');
          new AltCarousel(carouselEl);
          return;
        }
      } catch (err) {
        // Fallback to existing server-rendered HTML if API not reachable
      }

      // If already rendered via PHP or static fallback, initialize carousel
      if (track.children.length > 0) {
        new AltCarousel(carouselEl);
      }
    },

    renderInsightCard(item) {
      const targetAttr = item.is_external == 1 ? 'target="_blank" rel="noopener"' : '';
      return `
        <div class="alt-carousel-slide">
          <a class="alt-hov" href="${this.escapeHtml(item.link_url)}" ${targetAttr} style="border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; text-decoration: none; color: #1E2229; background: #FFFFFF; width: 100%;">
            <div style="height: 160px; background: ${this.escapeHtml(item.banner_bg)}; padding: 24px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: flex-end; gap: 4px">
              <span style="font-size: 12px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: ${this.escapeHtml(item.badge_color)}">${this.escapeHtml(item.top_badge)}</span>
              <span style="font-family: 'Montserrat', sans-serif; font-weight: 900; font-size: 28px; line-height: 1.08; color: ${this.escapeHtml(item.banner_text_color)}">${this.escapeHtml(item.title)}</span>
            </div>
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 10px; flex-grow: 1">
              <span style="font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #005B94">${this.escapeHtml(item.category)}</span>
              <h3 style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 19px; line-height: 1.35">${this.escapeHtml(item.headline)}</h3>
              <span style="font-size: 14px; line-height: 1.5; color: #4A5568">${this.escapeHtml(item.description)}</span>
              <span style="margin-top: auto; padding-top: 6px; font-size: 15px; font-weight: 600; color: #005B94">${this.escapeHtml(item.link_text)}</span>
            </div>
          </a>
        </div>
      `;
    },

    renderVideoCard(item) {
      const isUpload = item.video_type === 'upload';
      const isYtDirect =
        item.video_path.includes('youtube.com/watch') || item.video_path.includes('youtu.be/');
      const useModal = isUpload || isYtDirect;

      const modalAttrs = useModal
        ? `data-video-modal="true" data-video-type="${this.escapeHtml(item.video_type)}" data-video-path="${this.escapeHtml(item.video_path)}" data-video-title="${this.escapeHtml(item.title)}"`
        : '';
      const href = useModal ? '#' : this.escapeHtml(item.video_path);
      const targetAttr = !useModal ? 'target="_blank" rel="noopener"' : '';

      const bannerStyle = item.thumbnail_path
        ? `background-image: url('${this.escapeHtml(item.thumbnail_path)}');`
        : `background: ${this.escapeHtml(item.banner_bg)};`;

      return `
        <div class="alt-carousel-slide">
          <a class="alt-hov" href="${href}" ${targetAttr} ${modalAttrs} style="border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; text-decoration: none; color: #1E2229; background: #FFFFFF; width: 100%;">
            <div class="${item.thumbnail_path ? 'alt-video-banner-thumb' : ''}" style="height: 150px; ${bannerStyle} display: flex; align-items: center; justify-content: space-between; padding: 0 24px; box-sizing: border-box; position: relative;">
              ${item.thumbnail_path ? '<div class="alt-video-banner-overlay"></div>' : ''}
              <span style="font-size: 12px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: ${this.escapeHtml(item.badge_color)}; position: relative; z-index: 2">${this.escapeHtml(item.badge)}</span>
              <svg aria-hidden="true" width="56" height="56" viewBox="0 0 56 56" fill="none" style="position: relative; z-index: 2; flex-shrink: 0;"><circle cx="28" cy="28" r="27" fill="#FFD200"></circle><path d="M23 18.5 L38 28 L23 37.5 Z" fill="#1E2229"></path></svg>
            </div>
            <div style="padding: 22px 24px 24px; display: flex; flex-direction: column; gap: 8px; flex-grow: 1">
              <h4 style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 18px; line-height: 1.35">${this.escapeHtml(item.title)}</h4>
              <span style="font-size: 14px; color: #4A5568">${this.escapeHtml(item.meta_info)}</span>
              <span style="margin-top: auto; padding-top: 6px; font-size: 15px; font-weight: 600; color: #005B94">${this.escapeHtml(item.button_text || 'Watch video →')}</span>
            </div>
          </a>
        </div>
      `;
    },

    escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }
  };

  // Initialize on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    VideoLightbox.init();

    document.querySelectorAll('[data-carousel]').forEach((el) => {
      const type = el.dataset.carousel;
      DynamicLoader.syncFromAPI(el, type);
    });
  });

  window.AltCarousel = AltCarousel;
  window.VideoLightbox = VideoLightbox;
})();
