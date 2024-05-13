document.addEventListener("DOMContentLoaded", function () {
  const track = document.querySelector(".carousel_track");
  
  if (track) {
    const slides = Array.from(track.children);
    const nextButton = document.querySelector(".carousel_control.next");
    const prevButton = document.querySelector(".carousel_control.prev");
    let currentIndex = 0;
    let autoScrollTimer;

    // Function to calculate and set the track's position based on currentIndex
    const setTrackPosition = () => {
      const slideWidth = slides[0].getBoundingClientRect().width;
      const newTranslateX = slideWidth * currentIndex * -1;
      track.style.transform = `translateX(${newTranslateX}px)`;
    };

    // Function to move to the next slide, adjusting currentIndex
    const moveToNextSlide = () => {
      currentIndex = (currentIndex + 1) % slides.length;
      setTrackPosition();
    };

    // Function to move to the previous slide, adjusting currentIndex
    const moveToPrevSlide = () => {
      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
      setTrackPosition();
    };

    // Function to start automatic sliding
    const startAutoScroll = () => {
      if (autoScrollTimer) clearInterval(autoScrollTimer); // Clear existing timer if any
      autoScrollTimer = setInterval(moveToNextSlide, 10000);
    };

    // Function to temporarily stop automatic sliding and restart it after a delay
    const manualNavigation = () => {
      stopAutoScroll();
      setTimeout(startAutoScroll, 30000); // Extend the restart delay after manual navigation
    };

    // Function to stop automatic sliding
    const stopAutoScroll = () => {
      clearInterval(autoScrollTimer);
    };

    // Event listeners for manual navigation
    nextButton.addEventListener("click", () => {
      moveToNextSlide();
      manualNavigation();
    });

    prevButton.addEventListener("click", () => {
      moveToPrevSlide();
      manualNavigation();
    });

    // Swipe functionality for touch devices
    let touchStartX = 0;
    let touchEndX = 0;

    track.addEventListener(
      "touchstart",
      (evt) => {
        touchStartX = evt.touches[0].clientX;
      },
      { passive: true }
    );

    track.addEventListener(
      "touchmove",
      (evt) => {
        touchEndX = evt.touches[0].clientX;
      },
      { passive: true }
    );

    track.addEventListener(
      "touchend",
      () => {
        if (touchStartX - touchEndX > 50) {
          // Swipe left - next slide
          moveToNextSlide();
        } else if (touchStartX - touchEndX < -50) {
          // Swipe right - previous slide
          moveToPrevSlide();
        }
        stopAutoScroll();
        setTimeout(startAutoScroll, 30000); // Delay before restarting auto-scroll
      },
      { passive: true }
    );

    // Initialize carousel position and start automatic scrolling
    setTrackPosition();
    startAutoScroll();
  }
});
