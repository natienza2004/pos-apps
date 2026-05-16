document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-[-6px]');
            setTimeout(() => toast.remove(), 250);
        }, 3200);
    });
});
