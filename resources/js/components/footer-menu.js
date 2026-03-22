export default () => ({
    open: false,
    _overlay: null,

    toggle() {
        this.open = !this.open;
        this.updateUI();
    },

    close() {
        this.open = false;
        this.updateUI();
    },

    updateUI() {
        const footer = this.$el.closest('footer');

        if (footer) {
            footer.style.zIndex = this.open ? '100' : '';
        }

        if (this.open) {
            if (!this._overlay) {
                this._overlay = document.createElement('div');
                this._overlay.className = 'footer-menu-backdrop';
                this._overlay.addEventListener('click', () => this.close());
                document.body.appendChild(this._overlay);
            }
            requestAnimationFrame(() => this._overlay.classList.add('active'));
        } else if (this._overlay) {
            this._overlay.classList.remove('active');
            const overlay = this._overlay;
            setTimeout(() => overlay.remove(), 150);
            this._overlay = null;
        }
    }
});
