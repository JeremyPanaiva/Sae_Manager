document.querySelectorAll('.countdown').forEach(el => {
    const endDate = new Date(el.dataset.date).getTime();
    const updateCountdown = () => {
        const now = new Date().getTime();
        let distance = endDate - now;

        if (distance < 0) {
            el.innerText = "Délai dépassé";
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        el.innerText = `${days}j ${hours}h ${minutes}m ${seconds}s`;
    };

    updateCountdown();
    setInterval(updateCountdown, 1000);
});
