export default (initialUpvoted, initialVotesCount, productId, productSlug, isAuthenticated, csrfToken) => ({
    isUpvoted: initialUpvoted,
    votesCount: initialVotesCount,
    isLoading: false,
    errorMessage: '',
    isAuthenticated: isAuthenticated,
    csrfToken: csrfToken,

    async toggleUpvote() {
        if (this.isLoading) return;

        if (!this.isAuthenticated) {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'login-required-modal' } }));
            return;
        }

        this.isLoading = true;
        this.errorMessage = '';

        const method = this.isUpvoted ? 'DELETE' : 'POST';
        const url = `/api/products/${productSlug}/upvote`;

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'include',
            });

            const data = await response.json();

            if (response.ok) {
                this.isUpvoted = !this.isUpvoted;
                this.votesCount = data.votes_count;
            } else {
                this.errorMessage = data.message || 'An error occurred.';
                if (response.status === 409 || response.status === 404) {
                    this.votesCount = data.votes_count;
                    this.isUpvoted = (response.status === 409);
                }
            }
        } catch (error) {
            this.errorMessage = 'A network error occurred. Please try again.';
        } finally {
            this.isLoading = false;
            if (this.errorMessage) {
                setTimeout(() => this.errorMessage = '', 3000);
            }
        }
    }
});