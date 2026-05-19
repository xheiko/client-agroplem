(function () {
    const timer = setInterval(() => {
        if (document.querySelector('.bi-dashboard')) {
            clearInterval(timer);
            alert('BI Dashboard 24 загружен');
        }
    }, 300);
})();