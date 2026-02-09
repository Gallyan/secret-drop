export default () => ({
        extendDays: '7',
        showRevokeModal: false,
        pendingRevokeId: null,
        pendingRevokeEl: null,

        init() {
            this.$watch('showRevokeModal', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.$refs.confirmRevokeBtn.focus();
                    });
                }
            });
        },

        openRevokeModal(buttonEl) {
            this.pendingRevokeId = buttonEl.dataset.secretId;
            this.pendingRevokeEl = buttonEl;
            this.showRevokeModal = true;
        },

        closeRevokeModal() {
            this.showRevokeModal = false;
            this.pendingRevokeId = null;
            this.pendingRevokeEl = null;
        },

        async confirmRevoke() {
            if (this.pendingRevokeId && this.pendingRevokeEl) {
                this.showRevokeModal = false;
                await this.revoke(this.pendingRevokeId, this.pendingRevokeEl);
            }
        },

        async extend(buttonEl) {
            const secretId = buttonEl.dataset.secretId;
            const card = buttonEl.closest('[x-data]');
            const data = Alpine.$data(card);
            data.extending = true;

            try {
                const response = await fetch(`/admin/secrets/${secretId}/extend`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ days: parseInt(this.extendDays) }),
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    const result = await response.json();
                    alert(result.error || 'Error extending secret');
                }
            } catch (error) {
                alert('Connection error');
            } finally {
                data.extending = false;
            }
        },

        async revoke(secretId, buttonEl) {
            const card = buttonEl.closest('[x-data]');
            const data = Alpine.$data(card);
            data.revoking = true;

            try {
                const response = await fetch(`/admin/secrets/${secretId}/revoke`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    const result = await response.json();
                    alert(result.error || 'Error revoking secret');
                }
            } catch (error) {
                alert('Connection error');
            } finally {
                data.revoking = false;
            }
        },
});
