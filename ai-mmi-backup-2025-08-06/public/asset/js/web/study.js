$(document).ready(function() {
    // Handle study option button clicks (open form)
    $('.study-option-button').on('click', function(e) {
        const href = $(this).attr('href');
        if (href && href !== 'javascript:void(0);') {
            return true;
        }

        e.preventDefault();

        const $card = $(this).closest('.study-option-card');
        const $form = $card.find('.study-option-form');

        if ($form.length) {
            $form.toggleClass('is-open');
        }
    });

    // Handle form submissions
    $('.study-option-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const baseQuestion = $form.data('question') || '';
        const details = [];

        $form.find('[data-label]').each(function() {
            const value = ($(this).val() || '').trim();
            if (value) {
                details.push(`${$(this).data('label')}: ${value}`);
            }
        });

        const detailText = details.length ? `\n\nProfile details:\n- ${details.join('\n- ')}` : '';
        const fullPrompt = `${baseQuestion}${detailText}`;

        sendToChatbox(fullPrompt);
    });

    /**
     * Send a predefined message to the chatbox and expand it full-screen
     * so users can clearly see the AI response.
     */
    function sendToChatbox(message) {
        const $chatInput = $('#ask_question');

        if ($chatInput.length === 0) {
            console.error('Chat input not found');
            return;
        }

        $chatInput.val(message);

        // Expand chat to full screen on all device sizes
        const $chatArea = $('main.page-body div.chat-area');
        if (!$chatArea.hasClass('show-mobile')) {
            $chatArea.addClass('show-mobile');
            $('.mobile-chat-button').addClass('hidden');
        }
        $chatArea.addClass('chat-from-study');

        // Submit after a short delay so the panel is fully visible
        setTimeout(function() {
            $('#ask-form').submit();
        }, 200);
    }

    // Remove the study-back marker when chat is closed
    $(document).on('click', 'main.page-body div.chat-area div.box > a.btn-close-mobile', function() {
        $('main.page-body div.chat-area').removeClass('chat-from-study');
    });

    // Check if we need to trigger AI assessment after redirect from eligibility form
    if (window.triggerAssessment && window.assessmentPrompt) {
        // Scroll to chat area if mobile
        if (window.innerWidth <= 768) {
            if (typeof toggleMobileChat === 'function') {
                toggleMobileChat();
            }
        } else {
            // Scroll to chat on desktop
            $('html, body').animate({
                scrollTop: $('.chat-area').offset().top - 100
            }, 500);
        }
        
        // Wait a moment for the page to settle
        setTimeout(function() {
            // Set the message in the chat input
            $('#ask_question').val(window.assessmentPrompt);
            
            // Submit the chat form to get AI assessment
            $('#ask-form').submit();
        }, 800);
    }

    // Program search + slider for large listings (ApplyBoard-style experience)
    var searchInput = document.getElementById('program-search');
    var clearBtn = document.getElementById('program-search-clear');
    var gridWrap = document.getElementById('programs-grid-wrap');
    var grid = document.getElementById('programs-grid');
    var noResults = document.getElementById('programs-no-results');
    var controls = document.getElementById('programs-slider-controls');
    var prevBtn = document.getElementById('programs-slide-prev');
    var nextBtn = document.getElementById('programs-slide-next');
    var indicator = document.getElementById('programs-slide-indicator');

    if (searchInput && gridWrap && grid) {
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.program-card'));
        var sliderThreshold = 10;
        var currentPage = 1;

        function cardsPerPage() {
            var minCardWidth = 260;
            var gap = 22;
            var available = (gridWrap && gridWrap.clientWidth) ? gridWrap.clientWidth : window.innerWidth;

            // Keep two rows while adapting columns to actual available content width.
            var columns = 1;
            if (available >= (minCardWidth * 3) + (gap * 2)) {
                columns = 3;
            } else if (available >= (minCardWidth * 2) + gap) {
                columns = 2;
            }

            return columns * 2;
        }

        function updateSliderControls(totalVisible) {
            if (!controls || !indicator || !prevBtn || !nextBtn) return;

            var perPage = cardsPerPage();
            var totalPages = Math.max(1, Math.ceil(totalVisible / perPage));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            if (currentPage < 1) {
                currentPage = 1;
            }

            indicator.textContent = currentPage + ' / ' + totalPages;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
        }

        function setSliderMode(enabled) {
            if (enabled) {
                gridWrap.classList.add('is-slider');
                grid.classList.add('is-slider');
                if (controls) controls.style.display = '';
            } else {
                gridWrap.classList.remove('is-slider');
                grid.classList.remove('is-slider');
                if (controls) controls.style.display = 'none';
            }
        }

        function renderPage(filteredCards) {
            var perPage = cardsPerPage();
            var start = (currentPage - 1) * perPage;
            var end = start + perPage;

            cards.forEach(function(card) {
                card.style.display = 'none';
            });

            filteredCards.forEach(function(card, index) {
                if (index >= start && index < end) {
                    card.style.display = '';
                }
            });

            updateSliderControls(filteredCards.length);
        }

        function applyFilter() {
            var q = (searchInput.value || '').toLowerCase().trim();
            var filteredCards = [];

            cards.forEach(function(card) {
                var haystack = (card.getAttribute('data-search') || '').toLowerCase();
                var show = !q || haystack.indexOf(q) !== -1;
                if (show) {
                    filteredCards.push(card);
                }
            });

            if (clearBtn) {
                clearBtn.style.display = q ? 'inline-block' : 'none';
            }
            if (noResults) {
                noResults.style.display = filteredCards.length === 0 ? '' : 'none';
            }

            // Slider mode only when unfiltered and enough cards
            var useSlider = !q && cards.length >= sliderThreshold;
            setSliderMode(useSlider);

            if (useSlider) {
                currentPage = 1;
                renderPage(cards);
            } else {
                cards.forEach(function(card) {
                    card.style.display = 'none';
                });
                filteredCards.forEach(function(card) {
                    card.style.display = '';
                });
                currentPage = 1;
            }
        }

        searchInput.addEventListener('input', applyFilter);

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                applyFilter();
                searchInput.focus();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                var totalPages = Math.max(1, Math.ceil(cards.length / cardsPerPage()));
                if (currentPage > 1) {
                    currentPage--;
                    renderPage(cards);
                }
                updateSliderControls(cards.length);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                var totalPages = Math.max(1, Math.ceil(cards.length / cardsPerPage()));
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPage(cards);
                }
                updateSliderControls(cards.length);
            });
        }

        window.addEventListener('resize', function() {
            if (!gridWrap.classList.contains('is-slider')) return;
            renderPage(cards);
        });

        applyFilter();
    }
});
