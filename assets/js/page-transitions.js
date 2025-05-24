/**
 * GearPC Page Transitions
 * Enhances page transitions with pre-caching and smooth animations
 */

document.addEventListener('DOMContentLoaded', function() {
  // Initialize page transition effects
  initPageTransitions();
  
  // Initialize link pre-fetching for faster page loading
  initLinkPrefetching();
});

/**
 * Initialize page transition effects
 */
function initPageTransitions() {
  // Add fade-in effect to the body when page loads
  document.body.classList.add('fade-in');
  
  // Track when all page resources are loaded
  window.addEventListener('load', function() {
    document.body.classList.add('loaded');
    
    // Remove initial fade-in class after animation completes
    setTimeout(function() {
      document.body.classList.remove('fade-in');
    }, 500);
  });
  
  // Add transition effects for navigation
  document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    
    // Only apply transition effects to internal links
    if (link && 
        link.href && 
        !link.classList.contains('no-transition') && 
        !link.getAttribute('target') && 
        link.href.indexOf(window.location.hostname) > -1) {
      
      // Don't apply transitions to download links or anchor links
      if (link.getAttribute('download') || link.getAttribute('href').startsWith('#')) return;
      
      // Add smooth exit transition before navigating
      document.body.classList.add('fade-out');
    }
  });
}

/**
 * Initialize link pre-fetching for faster loading
 * This uses "Instant.page" technique to pre-fetch pages on hover/touchstart
 */
function initLinkPrefetching() {
  // Only prefetch on non-mobile devices to save bandwidth
  if (navigator.connection && (navigator.connection.saveData || navigator.connection.effectiveType.includes('2g'))) {
    return;
  }
  
  let hoveredLink = null;
  let prefetched = new Set();
  
  // Prefetch when hovering links for 100ms
  document.addEventListener('mouseover', function(e) {
    const link = e.target.closest('a');
    
    if (link && 
        link.href && 
        !link.classList.contains('no-prefetch') && 
        !link.getAttribute('download') && 
        link.href.indexOf(window.location.hostname) > -1 &&
        !link.href.startsWith(window.location.origin + '#') &&
        !prefetched.has(link.href)) {
      
      hoveredLink = link;
      
      setTimeout(function() {
        // Only prefetch if still hovering the same link
        if (hoveredLink === link) {
          // Create a prefetch link
          const prefetchLink = document.createElement('link');
          prefetchLink.rel = 'prefetch';
          prefetchLink.href = link.href;
          document.head.appendChild(prefetchLink);
          
          // Track prefetched URLs
          prefetched.add(link.href);
        }
      }, 100);
    }
  });
  
  // Clear hovered link reference
  document.addEventListener('mouseout', function(e) {
    if (e.target.closest('a') === hoveredLink) {
      hoveredLink = null;
    }
  });
}
