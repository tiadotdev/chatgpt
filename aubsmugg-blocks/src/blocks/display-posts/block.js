function loadMorePosts() {
    const loadMoreButtons = document.querySelectorAll('.display-posts-show-more-btn');

    loadMoreButtons.forEach((loadMoreButton) => {
        const postsList = loadMoreButton.closest('.aubsmugg-block-display-posts').querySelector('.display-posts-list');
        if (!postsList) return;

        const posts = postsList.querySelectorAll('li');
        let currentlyDisplayed = 12; // Start with 12 posts displayed

        const updatePostsDisplay = () => {
            // Hide all posts after the currently displayed ones
            posts.forEach((post, index) => {
                if (index >= currentlyDisplayed) {
                    post.style.display = 'none';
                }
            });
        };

        loadMoreButton.addEventListener('click', () => {
            // Show 6 more posts
            for (let i = currentlyDisplayed; i < currentlyDisplayed + 6; i++) {
                if (posts[i]) {
                    posts[i].style.display = 'block';
                }
            }

            currentlyDisplayed += 6;

            // Hide button if all posts are shown
            if (currentlyDisplayed >= posts.length) {
                loadMoreButton.style.display = 'none';
            }
        });

        // Initial update
        updatePostsDisplay();
    });

    // Use MutationObserver to watch for changes in the post list
    const observer = new MutationObserver(() => {
        loadMorePosts(); // Re-run the function when the DOM changes
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

// Call the function once the document is loaded
document.addEventListener('DOMContentLoaded', loadMorePosts);