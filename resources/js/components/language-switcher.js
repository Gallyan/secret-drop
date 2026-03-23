export default () => ({
    isOpen: false,
    highlightedIndex: 0,

    init() {
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const label = isMac ? '⌘K' : 'Ctrl+K';

        this.$el.querySelectorAll('[data-shortcut-label]').forEach(el => {
            el.textContent = label;
        });
    },

    openPalette() {
        this.isOpen = true;

        const items = this.getItems();
        const currentIndex = items.findIndex(item => item.getAttribute('aria-current') === 'true');
        this.highlightedIndex = currentIndex >= 0 ? currentIndex : 0;
        items.forEach(item => {
            item.style.display = '';
            item.removeAttribute('aria-selected');
        });

        this.updateHighlight();

        setTimeout(() => {
            this.$refs.searchInput.value = '';
            this.$refs.searchInput.focus();
            const visible = this.getVisibleItems();
            visible[this.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
        }, 100);
    },

    closePalette() {
        this.isOpen = false;
    },

    togglePalette() {
        if (this.isOpen) {
            this.closePalette();
        } else {
            this.openPalette();
        }
    },

    getItems() {
        return Array.from(this.$refs.results.querySelectorAll('[data-locale]'));
    },

    getVisibleItems() {
        return this.getItems().filter(item => item.style.display !== 'none');
    },

    activeDescendantId() {
        const visible = this.getVisibleItems();
        const item = visible[this.highlightedIndex];

        return item ? item.id : '';
    },

    filterResults() {
        const query = this.$refs.searchInput.value.toLowerCase();
        const items = this.getItems();

        items.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            const code = item.dataset.locale.toLowerCase();
            item.style.display = (name.includes(query) || code.includes(query)) ? '' : 'none';
        });

        this.highlightedIndex = 0;
        this.updateHighlight();

        const visible = this.getVisibleItems();
        this.$refs.resultCount.textContent = visible.length + ' / ' + items.length;
    },

    updateHighlight() {
        const visible = this.getVisibleItems();

        visible.forEach((item, i) => {
            const isActive = i === this.highlightedIndex;
            item.classList.toggle('cmd-highlighted', isActive);
            item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    },

    highlightItem(el) {
        const visible = this.getVisibleItems();
        const index = visible.indexOf(el);

        if (index >= 0) {
            this.highlightedIndex = index;
            this.updateHighlight();
        }
    },

    handleGlobalKeydown(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            if (!this.isOpen) {
                const tag = event.target.tagName;

                if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                    return;
                }
            }

            event.preventDefault();
            this.togglePalette();
        }

        if (event.key === 'Escape' && this.isOpen) {
            this.closePalette();
        }
    },

    handleKeydown(event) {
        const visible = this.getVisibleItems();

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.highlightedIndex = Math.min(this.highlightedIndex + 1, visible.length - 1);
            this.updateHighlight();
            visible[this.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
            this.updateHighlight();
            visible[this.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const item = visible[this.highlightedIndex];

            if (item) {
                window.location.href = item.href;
            }
        }
    },
});
