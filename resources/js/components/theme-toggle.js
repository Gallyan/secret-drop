export default () => ({
    dark: localStorage.getItem('theme') !== 'light',

    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    },

    ariaLabel() {
        const btn = this.$el;

        return this.dark ? btn.dataset.labelLight : btn.dataset.labelDark;
    }
});
