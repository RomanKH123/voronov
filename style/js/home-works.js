document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('projects-list');
    if (!container) return;

    fetch('/api/works.php')
        .then(function(response) {
            if (!response.ok) throw new Error('Failed to load projects');
            return response.json();
        })
        .then(function(data) {
            if (!data.success || !Array.isArray(data.data)) {
                container.innerHTML = errorBlock();
                return;
            }

            var allWorks = data.data;

            if (allWorks.length === 0) {
                container.innerHTML = emptyBlock();
                return;
            }

            container.innerHTML = '';

            // Первые шесть работ в порядке, сохранённом в панели портфолио.
            var worksToShow = allWorks.slice(0, 6);
            var hasMore = allWorks.length > 6;

            var section = document.createElement('div');
            section.className = 'projects_showcase category-section';

            // Сетка карточек
            var grid = document.createElement('div');
            grid.className = 'projects_grid';

            worksToShow.forEach(function(work) {
                var link = document.createElement('a');
                // Карточка всегда ведёт на описание конкретной работы.
                link.href = '/work_info.html?id=' + encodeURIComponent(work.id);
                link.className = 'project_card_link';

                var altText = escapeHtml(work.title) + (work.category ? ' — ' + escapeHtml(work.category) : '');
                var imgHtml = work.image
                    ? '<div class="project_preview"><img src="' + escapeHtml(work.image) + '" alt="' + altText + '" class="project_image" loading="lazy" onerror="this.parentElement.classList.add(\'project_preview--broken\');this.remove();"></div>'
                    : '<div class="project_preview project_preview--no-image"><span class="project_preview__placeholder">' + escapeHtml(work.title.charAt(0)) + '</span></div>';

                link.innerHTML =
                    '<article class="project_card">' +
                        imgHtml +
                        '<div class="project_details">' +
                            '<h3 class="project_name">' + escapeHtml(work.title) + '</h3>' +
                            '<p class="project_desc">' + escapeHtml(work.description) + '</p>' +
                        '</div>' +
                    '</article>';

                grid.appendChild(link);
            });

            section.appendChild(grid);

            // Все работы доступны на существующей странице портфолио.
            if (hasMore) {
                var btnWrap = document.createElement('div');
                btnWrap.className = 'category-more-wrap';
                var btn = document.createElement('a');
                btn.href = '/work.html';
                btn.className = 'category-more-btn';
                btn.textContent = 'Показать все';
                btnWrap.appendChild(btn);
                section.appendChild(btnWrap);
            }

            container.appendChild(section);

            // Анимация карточек
            setTimeout(function() {
                var cards = container.querySelectorAll('.project_card');
                cards.forEach(function(card, index) {
                    setTimeout(function() {
                        card.classList.add('visible');
                    }, index * 80);
                });
            }, 100);
        })
        .catch(function() {
            container.innerHTML = errorBlock();
        });

    function emptyBlock() {
        return '<div class="projects_showcase category-section">' +
            '<div class="projects-empty">' +
                '<div class="projects-empty__icon">' +
                    '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#4a6fa5" stroke-width="1.2">' +
                        '<rect x="2" y="3" width="20" height="14" rx="2"/>' +
                        '<path d="M8 21h8"/><path d="M12 17v4"/>' +
                    '</svg>' +
                '</div>' +
                '<h3 class="projects-empty__title">Скоро здесь появятся наши работы</h3>' +
                '<p class="projects-empty__text">Мы готовим портфолио лучших проектов. Загляните чуть позже!</p>' +
            '</div>' +
        '</div>';
    }

    function errorBlock() {
        return '<div class="projects_showcase category-section">' +
            '<div class="projects-empty projects-empty--error">' +
                '<div class="projects-empty__icon">' +
                    '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#4a6fa5" stroke-width="1.2">' +
                        '<circle cx="12" cy="12" r="10"/>' +
                        '<path d="M12 8v4"/><circle cx="12" cy="16" r="0.5" fill="#4a6fa5"/>' +
                    '</svg>' +
                '</div>' +
                '<h3 class="projects-empty__title">Не удалось загрузить работы</h3>' +
                '<p class="projects-empty__text">Проверьте соединение с интернетом и попробуйте обновить страницу</p>' +
                '<button class="category-more-btn" onclick="location.reload()">Обновить страницу</button>' +
            '</div>' +
        '</div>';
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
