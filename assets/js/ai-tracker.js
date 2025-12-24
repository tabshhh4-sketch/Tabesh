/**
 * AI Behavior Tracker JavaScript
 *
 * Tracks user behavior for AI-powered personalization.
 *
 * @package Tabesh
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        trackingEnabled: tabeshAIBrowser.trackingEnabled,
        debounceDelay: 500,
        batchSize: 10,
        batchDelay: 5000
    };

    // State
    let eventQueue = [];
    let batchTimer = null;
    let scrollTimer = null;
    let idleTimer = null;
    let lastScrollPosition = 0;
    let lastActivityTime = Date.now();

    /**
     * Initialize tracker
     */
    function initTracker() {
        if (!config.trackingEnabled) {
            return;
        }

        setupEventListeners();
        trackPageView();
        startBatchProcessor();
        startIdleDetector();
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Scroll tracking with depth milestones
        $(window).on('scroll', debounce(function() {
            trackScroll();
            trackScrollDepth();
        }, config.debounceDelay));

        // Click tracking with confusion detection
        $(document).on('click', 'a, button', function(e) {
            trackClick(e);
            detectConfusedUser(e);
        });

        // Form field interactions
        $('input, textarea, select').on('focus', function(e) {
            trackFormFocus(e);
        });

        $('input, textarea, select').on('change', function(e) {
            trackFormChange(e);
        });

        // Page visibility
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                trackEvent('page_hidden', {
                    time_spent: Date.now() - lastActivityTime,
                    engagement_score: calculateEngagement()
                });
                flushQueue();
            } else {
                lastActivityTime = Date.now();
                trackEvent('page_visible', {});
            }
        });

        // Before unload
        $(window).on('beforeunload', function() {
            flushQueue();
        });
    }

    /**
     * Track page view
     */
    function trackPageView() {
        trackEvent('page_view', {
            page_url: window.location.href,
            page_title: document.title,
            referrer: document.referrer,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight
        });
    }

    /**
     * Track scroll position
     */
    function trackScroll() {
        const scrollTop = $(window).scrollTop();
        const documentHeight = $(document).height();
        const windowHeight = $(window).height();
        const scrollPercent = Math.round((scrollTop / (documentHeight - windowHeight)) * 100);

        // Only track significant scroll changes (10% increments)
        if (Math.abs(scrollPercent - lastScrollPosition) >= 10) {
            trackEvent('scroll', {
                scroll_position: scrollTop,
                scroll_percent: scrollPercent,
                direction: scrollPercent > lastScrollPosition ? 'down' : 'up'
            });

            lastScrollPosition = scrollPercent;
        }

        updateActivity();
    }

    /**
     * Track click event
     */
    function trackClick(event) {
        const $target = $(event.currentTarget);
        const elementType = $target.prop('tagName').toLowerCase();
        const elementId = $target.attr('id') || '';
        const elementClass = $target.attr('class') || '';
        const elementText = $target.text().trim().substring(0, 50);
        const href = $target.attr('href') || '';

        trackEvent('click', {
            element_type: elementType,
            element_id: elementId,
            element_class: elementClass,
            element_text: elementText,
            href: href,
            x: event.pageX,
            y: event.pageY
        });

        updateActivity();
    }

    /**
     * Track form field focus
     */
    function trackFormFocus(event) {
        const $target = $(event.target);
        const fieldName = $target.attr('name') || $target.attr('id') || '';
        const fieldType = $target.attr('type') || $target.prop('tagName').toLowerCase();

        trackEvent('form_focus', {
            field_name: fieldName,
            field_type: fieldType
        });

        updateActivity();
    }

    /**
     * Track form field change
     */
    function trackFormChange(event) {
        const $target = $(event.target);
        const fieldName = $target.attr('name') || $target.attr('id') || '';
        const fieldType = $target.attr('type') || $target.prop('tagName').toLowerCase();
        let fieldValue = $target.val();

        // Don't track sensitive data
        if (fieldType === 'password' || fieldType === 'email' || fieldName.includes('credit')) {
            fieldValue = '[REDACTED]';
        } else if (typeof fieldValue === 'string' && fieldValue.length > 100) {
            fieldValue = fieldValue.substring(0, 100) + '...';
        }

        trackEvent('form_change', {
            field_name: fieldName,
            field_type: fieldType,
            field_value: fieldValue
        });

        updateActivity();
    }

    /**
     * Track generic event
     */
    function trackEvent(eventType, eventData) {
        if (!config.trackingEnabled) {
            return;
        }

        // Add common data
        const event = {
            event_type: eventType,
            event_data: Object.assign({
                page_url: window.location.href,
                referrer: document.referrer,
                timestamp: Date.now()
            }, eventData),
            guest_uuid: window.tabeshAIBrowserAPI ? window.tabeshAIBrowserAPI.getGuestUUID() : null
        };

        // Add to queue
        eventQueue.push(event);

        // Flush if queue is full
        if (eventQueue.length >= config.batchSize) {
            flushQueue();
        }
    }

    /**
     * Flush event queue
     */
    function flushQueue() {
        if (eventQueue.length === 0) {
            return;
        }

        const events = eventQueue.splice(0, config.batchSize);

        // Send to server
        $.ajax({
            url: tabeshAIBrowser.ajaxUrl + '/ai/browser/track',
            method: 'POST',
            headers: {
                'X-WP-Nonce': tabeshAIBrowser.nonce
            },
            contentType: 'application/json',
            data: JSON.stringify(events[0]), // For now, send one at a time
            async: true
        });

        // Process remaining events
        if (events.length > 1) {
            events.slice(1).forEach(function(event) {
                $.ajax({
                    url: tabeshAIBrowser.ajaxUrl + '/ai/browser/track',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': tabeshAIBrowser.nonce
                    },
                    contentType: 'application/json',
                    data: JSON.stringify(event),
                    async: true
                });
            });
        }
    }

    /**
     * Start batch processor
     */
    function startBatchProcessor() {
        batchTimer = setInterval(function() {
            flushQueue();
        }, config.batchDelay);
    }

    /**
     * Start idle detector
     */
    function startIdleDetector() {
        idleTimer = setInterval(function() {
            const idleTime = Date.now() - lastActivityTime;

            // If idle for more than 30 seconds
            if (idleTime > 30000) {
                trackEvent('idle', {
                    idle_duration: idleTime
                });
                lastActivityTime = Date.now();
            }
        }, 30000);
    }

    /**
     * Update last activity time
     */
    function updateActivity() {
        lastActivityTime = Date.now();
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Track scroll depth milestones
     */
    function trackScrollDepth() {
        const milestones = [25, 50, 75, 90, 100];
        const scrollPercent = Math.round(($(window).scrollTop() / ($(document).height() - $(window).height())) * 100);
        
        milestones.forEach(function(milestone) {
            const key = 'scroll_milestone_' + milestone;
            if (scrollPercent >= milestone && !sessionStorage.getItem(key)) {
                sessionStorage.setItem(key, 'true');
                trackEvent('scroll_milestone', {
                    milestone: milestone,
                    page_url: window.location.href
                });
            }
        });
    }

    /**
     * Detect confused user patterns
     */
    let clickPatterns = [];
    function detectConfusedUser(event) {
        const $target = $(event.currentTarget);
        const elementInfo = {
            id: $target.attr('id') || '',
            class: $target.attr('class') || '',
            time: Date.now()
        };
        
        clickPatterns.push(elementInfo);
        
        // Keep only last 10 clicks
        if (clickPatterns.length > 10) {
            clickPatterns.shift();
        }
        
        // Check for rapid repeated clicks on same element (confusion indicator)
        const recentClicks = clickPatterns.slice(-5);
        const sameElementClicks = recentClicks.filter(function(click) {
            return click.id === elementInfo.id && click.class === elementInfo.class;
        });
        
        if (sameElementClicks.length >= 3) {
            trackEvent('confused_user', {
                element_id: elementInfo.id,
                element_class: elementInfo.class,
                click_count: sameElementClicks.length,
                pattern: 'rapid_same_element'
            });
            
            // Clear pattern to avoid duplicate tracking
            clickPatterns = [];
        }
    }

    /**
     * Track reading engagement
     */
    let readingStartTime = Date.now();
    let engagementScore = 0;
    
    function calculateEngagement() {
        const timeOnPage = (Date.now() - readingStartTime) / 1000; // seconds
        const scrollDepth = Math.round(($(window).scrollTop() / ($(document).height() - $(window).height())) * 100);
        const clickCount = clickPatterns.length;
        
        // Simple engagement score: time + scroll + interactions
        engagementScore = Math.min(100, Math.round(
            (timeOnPage / 60) * 30 + // Up to 30 points for time (max at 2 min)
            (scrollDepth / 100) * 40 + // Up to 40 points for scroll
            Math.min(clickCount * 3, 30) // Up to 30 points for interactions
        ));
        
        return engagementScore;
    }

    /**
     * Track engagement on page leave
     */
    $(window).on('beforeunload', function() {
        const finalEngagement = calculateEngagement();
        trackEvent('engagement_score', {
            score: finalEngagement,
            time_on_page: (Date.now() - readingStartTime) / 1000,
            scroll_depth: Math.round(($(window).scrollTop() / ($(document).height() - $(window).height())) * 100),
            interactions: clickPatterns.length
        });
    });

    // Initialize on document ready
    $(document).ready(function() {
        initTracker();
    });

    // Expose API
    window.tabeshAITracker = {
        trackEvent: trackEvent,
        flushQueue: flushQueue,
        getEngagementScore: calculateEngagement,
        detectConfusion: detectConfusedUser
    };

})(jQuery);
