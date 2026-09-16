const calendar = document.querySelector('#mobile-calendar');

document.querySelector('[data-calendar-open]')?.addEventListener('click', () => {
    calendar?.showModal();
});

document.querySelector('[data-calendar-close]')?.addEventListener('click', () => {
    calendar?.close();
});

calendar?.addEventListener('click', (event) => {
    if (event.target === calendar) {
        calendar.close();
    }
});
