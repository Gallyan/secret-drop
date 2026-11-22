export default () => ({
    dark: localStorage.getItem('theme') !== 'light',

    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
        document.querySelector('meta[name="theme-color"]').content = this.dark ? '#0f172a' : '#f9fafb';
    },

    ariaLabel() {
        return this.dark
            ? this.$el.dataset.labelLight
            : this.$el.dataset.labelDark;
    }
});
