window.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.seometa-menu__button');
    const contentAreas = document.querySelectorAll('.seometa-menu__content');

    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].addEventListener('click', () => {

            tabButtons.forEach(
                button => {
                    button.classList.remove('seometa-menu__button_active');
                }
            );

            tabButtons[i].classList.add('seometa-menu__button_active');

            contentAreas.forEach(
                area => {
                    area.style.display = 'none';
                }
            );

            contentAreas[i].style.display = 'flex';
        })
    }
});
