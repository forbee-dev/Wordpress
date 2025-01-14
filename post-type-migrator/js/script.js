jQuery(document).ready(function($) {
    // Add heartbeat nonce refresh
    $(document).on('heartbeat-send', function(e, data) {
        data.post_type_migrator_heartbeat = true;
    });

    $(document).on('heartbeat-tick', function(e, data) {
        if (data.post_type_migrator_new_nonce) {
            postMigratorData.security = data.post_type_migrator_new_nonce;
            console.log('Nonce refreshed');
        }
    });

    // Source type selection
    $('#source-type').on('change', function() {
        const sourceType = $(this).val();
        $('#source-post-type-container, #source-taxonomy-container').hide();
        
        if (sourceType === 'post-type') {
            $('#source-post-type-container').show();
        } else if (sourceType === 'taxonomy') {
            $('#source-taxonomy-container').show();
        }
        
        $('#posts-list').empty();
        updateMigrateButton();
    });

    // Source post type selection
    $('#source-post-type').on('change', function() {
        const postType = $(this).val();
        if (postType) {
            // Show category filter only for 'post' type
            if (postType === 'post') {
                $('#category-filter-container').show();
            } else {
                $('#category-filter-container').hide();
            }
            loadPosts('post-type', postType);
        }
    });

    // Source taxonomy selection
    $('#source-taxonomy').on('change', function() {
        const taxonomy = $(this).val();
        if (taxonomy) {
            // Load taxonomy terms
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_taxonomy_terms',
                    taxonomy: taxonomy,
                    nonce: postMigrator.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderTerms(response.data);
                    }
                }
            });
        }
    });

    // Load posts when term is selected
    $(document).on('change', '#term-select', function() {
        if ($(this).val()) {
            loadPosts('taxonomy', $('#source-taxonomy').val(), $(this).val());
        }
    });

    // Select all posts checkbox
    $(document).on('change', '#select-all-posts', function() {
        $('.post-checkbox').prop('checked', $(this).prop('checked'));
        updateMigrateButton();
    });

    // Individual post checkbox
    $(document).on('change', '.post-checkbox', function() {
        updateMigrateButton();
    });

    // Migrate button click
    $('#migrate-button').on('click', async function() {
        const $button = $(this);
        const $progress = $('#migration-progress');
        
        try {
            const deleteOriginal = $('#delete-original').prop('checked');
            
            if (deleteOriginal) {
                const confirmed = confirm(
                    'Warning: You have chosen to delete the original posts after migration. ' +
                    'This action cannot be undone. Are you sure you want to continue?'
                );
                if (!confirmed) {
                    return;
                }
            }

            $button.prop('disabled', true);
            
            const selectedPosts = $('.post-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            const destinationType = $('#destination-post-type').val();

            if (!selectedPosts.length || !destinationType) {
                alert('Please select posts and destination post type');
                return;
            }

            // Initialize progress display
            $progress.html(`
                <div class="migration-progress-container">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-text">Starting migration...</div>
                    <div class="batch-progress"></div>
                    <div class="migration-summary"></div>
                </div>
            `);

            const totals = { success: 0, failed: 0 };
            const response = await processBatch(selectedPosts, destinationType, 0, totals, deleteOriginal);
            
            if (response.success) {
                displayMigrationResults(response.data);
            }
        } catch (error) {
            console.error('Migration error:', error);
            handleError({
                message: error.message || 'Migration failed',
                details: 'Please check the console for more information'
            });
        } finally {
            $button.prop('disabled', false);
        }
    });

    // Category filter change
    $('#category-filter').on('change', function() {
        const postType = $('#source-post-type').val();
        if (postType) {
            loadPosts('post-type', postType);
        }
    });

    function loadPosts(page = 1) {
        const sourceType = $('#source-type').val();
        const sourceValue = sourceType === 'post-type' ? $('#source-post-type').val() : $('#source-taxonomy').val();
        const selectedTerms = $('#category-filter').val() || [];

        $.ajax({
            url: postMigratorData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_posts_for_migration',
                security: postMigratorData.loadNonce,
                sourceType: sourceType,
                sourceValue: sourceValue,
                termIds: selectedTerms,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    updatePostsList(response.data.posts);
                    updatePagination(response.data.pagination);
                } else {
                    console.error('Error loading posts:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    function renderPosts(posts, pagination) {
        const $postsList = $('#posts-list');
        $postsList.empty();

        if (posts.length === 0) {
            $postsList.html('<p>No posts found</p>');
            return;
        }

        // Add pagination info
        const $paginationInfo = $(`
            <div class="pagination-info">
                <p>Showing page ${pagination.current_page} of ${pagination.total_pages} (${pagination.total_posts} total posts)</p>
            </div>
        `);
        $postsList.append($paginationInfo);

        // Add select all checkbox
        const $selectAll = $('<div class="post-item">' +
            '<input type="checkbox" id="select-all-posts">' +
            '<label for="select-all-posts">Select All on This Page</label>' +
            '</div>');
        $postsList.append($selectAll);

        // Add posts
        posts.forEach(function(post) {
            const $post = $('<div class="post-item">' +
                '<input type="checkbox" class="post-checkbox" value="' + post.id + '">' +
                '<span class="post-title">' + post.title + '</span>' +
                '<span class="post-meta">(' + post.type + ' - ' + post.date + ' - ' + post.status +
                (post.categories ? ' - Categories: ' + post.categories : '') + ')</span>' +
                '</div>');
            $postsList.append($post);
        });

        // Add pagination controls
        if (pagination.total_pages > 1) {
            const $paginationControls = $('<div class="pagination-controls"></div>');
            
            // Previous button
            if (pagination.current_page > 1) {
                $paginationControls.append(`
                    <button class="button page-button" data-page="${pagination.current_page - 1}">Previous</button>
                `);
            }

            // Page numbers
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (
                    i === 1 || 
                    i === pagination.total_pages || 
                    (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)
                ) {
                    $paginationControls.append(`
                        <button class="button page-button ${i === pagination.current_page ? 'current' : ''}" 
                                data-page="${i}">${i}</button>
                    `);
                } else if (
                    i === pagination.current_page - 3 || 
                    i === pagination.current_page + 3
                ) {
                    $paginationControls.append('<span class="pagination-ellipsis">...</span>');
                }
            }

            // Next button
            if (pagination.current_page < pagination.total_pages) {
                $paginationControls.append(`
                    <button class="button page-button" data-page="${pagination.current_page + 1}">Next</button>
                `);
            }

            $postsList.append($paginationControls);
        }

        // Add pagination button handlers
        $('.page-button').on('click', function() {
            const page = $(this).data('page');
            const sourceType = $('#source-type').val();
            const sourceValue = sourceType === 'taxonomy' ? $('#source-taxonomy').val() : $('#source-post-type').val();
            const termId = $('#term-select').val();
            loadPosts(sourceType, sourceValue, termId, page);
        });
    }

    function renderTerms(terms) {
        const $termsContainer = $('#terms-container');
        $termsContainer.empty();

        if (terms.length === 0) {
            $termsContainer.html('<p>No terms found</p>');
            return;
        }

        const $select = $('<select id="term-select">' +
            '<option value="">Select Term</option>' +
            '</select>');

        terms.forEach(function(term) {
            $select.append('<option value="' + term.term_id + '">' + term.name + '</option>');
        });

        $termsContainer.append($select);
    }

    function updateMigrateButton() {
        const hasSelectedPosts = $('.post-checkbox:checked').length > 0;
        const hasDestination = $('#destination-post-type').val() !== '';
        $('#migrate-button').prop('disabled', !(hasSelectedPosts && hasDestination));
    }

    async function processBatch(allPosts, destType, batchNumber, totals, deleteOriginal) {
        const formData = new FormData();
        formData.append('action', 'migrate_selected_posts');
        formData.append('security', postMigratorData.migrateNonce);
        formData.append('destinationType', destType);
        formData.append('batchNumber', batchNumber);
        formData.append('totalPosts', allPosts.length);
        formData.append('deleteOriginal', deleteOriginal);
        
        allPosts.forEach(postId => {
            formData.append('postIds[]', postId);
        });

        return new Promise((resolve, reject) => {
            $.ajax({
                url: postMigratorData.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (!response.success) {
                        reject(new Error(response.data?.message || 'Unknown error'));
                        return;
                    }

                    totals.redirects = totals.redirects || [];
                    if (response.data.summary.redirects) {
                        totals.redirects = totals.redirects.concat(response.data.summary.redirects);
                    }
                    
                    resolve({
                        success: true,
                        data: {
                            summary: {
                                success: response.data.summary.success,
                                failed: response.data.summary.failed,
                                skipped: response.data.summary.skipped,
                                total: response.data.summary.total,
                                deleteOriginal: response.data.summary.deleteOriginal,
                                redirects: totals.redirects
                            },
                            results: response.data.results
                        }
                    });
                },
                error: function(xhr, status, error) {
                    reject(new Error(error || status));
                }
            });
        });
    }

    function handleError(error) {
        const $progress = $('#migration-progress');
        $progress.append(`
            <div class="error">
                <p>${error.message || 'An error occurred during migration'}</p>
                ${error.details ? `<p>${error.details}</p>` : ''}
            </div>
        `);
    }

    function displayMigrationResults(data) {
        const $progress = $('#migration-progress');
        
        // Count redirects from the summary
        const redirectsCreated = data.summary?.redirects?.length || 0;
        
        // Display summary
        $progress.html(`
            <div class="migration-summary">
                <h3>Migration Complete</h3>
                <p>Total Posts Processed: ${data.summary?.total || 0}</p>
                <p class="success">Successfully Migrated: ${data.summary?.success || 0}</p>
                <p class="warning">Skipped (Already Exist): ${data.summary?.skipped?.total || 0} 
                    ${data.summary?.skipped?.deleted ? 
                        `(${data.summary?.skipped?.deleted} originals deleted)` : 
                        '(originals preserved)'}</p>
                <p class="error">Failed: ${data.summary?.failed || 0}</p>
                <h4>Redirects Created: ${redirectsCreated}</h4>
            </div>
        `);

        // Display redirect details if available
        if (data.summary?.redirects?.length > 0) {
            const $redirects = $('<div class="redirect-details"><h3>Redirect Details</h3></div>');
            
            data.summary.redirects.forEach(function(result) {
                if (result.redirect && result.redirect.success) {
                    $redirects.append(`
                        <div class="redirect-info success">
                            <strong>Redirect Created:</strong><br>
                            From: ${result.redirect.source}<br>
                            To: ${result.redirect.target}<br>
                            ID: ${result.redirect.id}
                        </div>
                    `);
                }
            });
            
            $progress.append($redirects);
        }

        // Add a link to the Redirection plugin admin page if redirects were created
        if (redirectsCreated > 0) {
            $progress.append(`
                <div class="redirect-admin-link">
                    <p><a href="${ajaxurl.replace('admin-ajax.php', 'tools.php?page=redirection.php')}" 
                          target="_blank" class="button">
                        View All Redirects in Redirection Plugin
                    </a></p>
                </div>
            `);
        }

        // Add debug information
        console.log('Migration results data:', data);
    }

    function updatePostsList(posts) {
        const $postsList = $('#posts-list');
        $postsList.empty();

        if (posts.length === 0) {
            $postsList.html('<p>No posts found</p>');
            return;
        }

        // Add select all checkbox
        const $selectAll = $('<div class="post-item">' +
            '<input type="checkbox" id="select-all-posts">' +
            '<label for="select-all-posts">Select All on This Page</label>' +
            '</div>');
        $postsList.append($selectAll);

        // Add posts
        posts.forEach(function(post) {
            const $post = $('<div class="post-item">' +
                '<input type="checkbox" class="post-checkbox" value="' + post.id + '">' +
                '<span class="post-title">' + post.title + '</span>' +
                '<span class="post-meta">(' + post.type + ' - ' + post.date + ' - ' + post.status +
                (post.categories ? ' - Categories: ' + post.categories : '') + ')</span>' +
                '</div>');
            $postsList.append($post);
        });
    }

    function updatePagination(pagination) {
        const $paginationContainer = $('#pagination-container');
        if (!$paginationContainer.length) {
            $('#posts-list').after('<div id="pagination-container"></div>');
        }
        
        const $pagination = $('#pagination-container');
        $pagination.empty();

        if (pagination.total_pages <= 1) {
            return;
        }

        // Add pagination info
        $pagination.append(`
            <div class="pagination-info">
                <p>Showing page ${pagination.current_page} of ${pagination.total_pages} (${pagination.total_posts} total posts)</p>
            </div>
        `);

        // Add pagination controls
        const $controls = $('<div class="pagination-controls"></div>');
        
        // Previous button
        if (pagination.current_page > 1) {
            $controls.append(`
                <button class="button page-button" data-page="${pagination.current_page - 1}">Previous</button>
            `);
        }

        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (
                i === 1 || 
                i === pagination.total_pages || 
                (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)
            ) {
                $controls.append(`
                    <button class="button page-button ${i === pagination.current_page ? 'current' : ''}" 
                            data-page="${i}">${i}</button>
                `);
            } else if (
                i === pagination.current_page - 3 || 
                i === pagination.current_page + 3
            ) {
                $controls.append('<span class="pagination-ellipsis">...</span>');
            }
        }

        // Next button
        if (pagination.current_page < pagination.total_pages) {
            $controls.append(`
                <button class="button page-button" data-page="${pagination.current_page + 1}">Next</button>
            `);
        }

        $pagination.append($controls);

        // Add pagination button handlers
        $('.page-button').on('click', function() {
            const page = $(this).data('page');
            loadPosts(page);
        });
    }
}); 