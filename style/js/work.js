document.addEventListener('DOMContentLoaded', function() {
    const workGrid = document.querySelector('.work-grid');
    const workInfo = document.querySelector('.work-info');

    // Страница списка работ
    if (workGrid) {
        loadWorks();
    }

    // Страница детальной информации
    if (workInfo) {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');
        if (id) {
            loadWorkInfo(id);
        } else {
            workInfo.innerHTML = notFoundBlock();
        }
    }

    function notFoundBlock() {
        return '<div class="work-placeholder">' +
            '<div class="work-placeholder__icon">&#128269;</div>' +
            '<h3 class="work-placeholder__title">Работа не найдена</h3>' +
            '<p class="work-placeholder__text">Возможно, проект был удалён или ссылка устарела.</p>' +
            '<a href="/work.html" class="work-placeholder__btn">К списку работ</a>' +
        '</div>';
    }

    function loadWorks() {
        workGrid.innerHTML = '<div class="work-loading"><span class="loading-spinner"></span> Загрузка работ...</div>';

        fetch('/api/works.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data.length > 0) {
                    renderWorks(data.data);
                } else {
                    workGrid.innerHTML =
                        '<div class="work-placeholder">' +
                            '<div class="work-placeholder__icon">&#128736;</div>' +
                            '<h3 class="work-placeholder__title">Работы скоро появятся</h3>' +
                            '<p class="work-placeholder__text">Мы готовим портфолио наших лучших проектов. Загляните чуть позже!</p>' +
                            '<a href="/" class="work-placeholder__btn">На главную</a>' +
                        '</div>';
                }
            })
            .catch(function() {
                workGrid.innerHTML =
                    '<div class="work-placeholder work-placeholder--error">' +
                        '<div class="work-placeholder__icon">&#9888;&#65039;</div>' +
                        '<h3 class="work-placeholder__title">Не удалось загрузить</h3>' +
                        '<p class="work-placeholder__text">Произошла ошибка при загрузке работ. Проверьте соединение и попробуйте ещё раз.</p>' +
                        '<button class="work-placeholder__btn" onclick="location.reload()">Обновить страницу</button>' +
                    '</div>';
            });
    }

    function renderWorks(works) {
        workGrid.innerHTML = '';
        var itemListElements = [];

        works.forEach(function(work, index) {
            var card = document.createElement('a');
            card.href = '/work_info.html?id=' + work.id;
            card.className = 'work-card';
            card.style.animationDelay = (index * 0.1) + 's';

            var altText = escapeHtml(work.title) + (work.category ? ' — ' + escapeHtml(work.category) + ', разработка сайта под ключ Краснодар' : '');
            var img = work.image ? '<div class="work-card__image"><img src="' + escapeHtml(work.image) + '" alt="' + altText + '" loading="lazy"></div>' : '';
            var category = work.category ? '<span class="work-card__category">' + escapeHtml(work.category) + '</span>' : '';

            card.innerHTML = img +
                '<div class="work-card__body">' +
                    category +
                    '<h3 class="work-card__title">' + escapeHtml(work.title) + '</h3>' +
                    '<p class="work-card__desc">' + escapeHtml(work.description) + '</p>' +
                '</div>';

            workGrid.appendChild(card);

            itemListElements.push({
                "@type": "ListItem",
                "position": index + 1,
                "name": work.title,
                "url": "https://voronov-art.ru/work_info.html?id=" + work.id
            });
        });

        // JSON-LD ItemList для поисковиков
        var ldScript = document.createElement('script');
        ldScript.type = 'application/ld+json';
        ldScript.textContent = JSON.stringify({
            "@context": "https://schema.org",
            "@type": "ItemList",
            "name": "Портфолио студии Воронова",
            "description": "Примеры разработанных сайтов под ключ в Краснодаре",
            "numberOfItems": works.length,
            "itemListElement": itemListElements
        });
        document.head.appendChild(ldScript);
    }

    function loadWorkInfo(id) {
        workInfo.innerHTML = '<div class="work-loading"><span class="loading-spinner"></span> Загрузка...</div>';

        fetch('/api/works.php?id=' + encodeURIComponent(id))
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    renderWorkInfo(data.data);
                } else {
                    workInfo.innerHTML = notFoundBlock();
                }
            })
            .catch(function() {
                workInfo.innerHTML =
                    '<div class="work-placeholder work-placeholder--error">' +
                        '<div class="work-placeholder__icon">&#9888;&#65039;</div>' +
                        '<h3 class="work-placeholder__title">Не удалось загрузить</h3>' +
                        '<p class="work-placeholder__text">Произошла ошибка при загрузке. Проверьте соединение и попробуйте ещё раз.</p>' +
                        '<button class="work-placeholder__btn" onclick="location.reload()">Обновить страницу</button>' +
                    '</div>';
            });
    }

    function renderWorkInfo(work) {
        var detailAlt = escapeHtml(work.title) + (work.category ? ' — ' + escapeHtml(work.category) + ', разработка студии Воронова Краснодар' : '');
        var image = work.image ? '<div class="work-detail__image"><img src="' + escapeHtml(work.image) + '" alt="' + detailAlt + '"></div>' : '';
        var category = work.category ? '<span class="work-detail__category">' + escapeHtml(work.category) + '</span>' : '';
        var link = work.url ? '<a href="' + escapeHtml(work.url) + '" class="work-detail__link" target="_blank">Смотреть проект</a>' : '';

        document.title = work.title + ' — Студия Воронова';

        updateMeta('description', work.full_description || work.description);
        updateMeta('og:title', work.title + ' — Студия Воронова', true);
        updateMeta('og:description', work.description, true);
        if (work.image) updateMeta('og:image', 'https://voronov-art.ru' + work.image, true);
        updateMeta('twitter:title', work.title + ' — Студия Воронова', true);
        updateMeta('twitter:description', work.description, true);
        if (work.image) updateMeta('twitter:image', 'https://voronov-art.ru' + work.image, true);

        workInfo.innerHTML =
            '<a href="/work.html" class="back-link">&larr; Вернуться к списку работ</a>' +
            image +
            '<div class="work-detail__content">' +
                category +
                '<h1 class="work-detail__title">' + escapeHtml(work.title) + '</h1>' +
                '<div class="work-detail__text">' + escapeHtml(work.full_description) + '</div>' +
                link +
            '</div>';

        // JSON-LD CreativeWork для поисковиков
        var ldData = {
            "@context": "https://schema.org",
            "@type": "CreativeWork",
            "name": work.title,
            "description": work.full_description || work.description,
            "url": "https://voronov-art.ru/work_info.html?id=" + work.id,
            "author": {
                "@type": "Organization",
                "name": "Студия Воронова",
                "url": "https://voronov-art.ru"
            },
            "genre": work.category || "Веб-разработка"
        };
        if (work.image) ldData.image = "https://voronov-art.ru" + work.image;

        var ldScript = document.createElement('script');
        ldScript.type = 'application/ld+json';
        ldScript.textContent = JSON.stringify(ldData);
        document.head.appendChild(ldScript);
    }

    function updateMeta(name, content, isProperty) {
        if (!content) return;
        var attr = isProperty ? 'property' : 'name';
        var el = document.querySelector('meta[' + attr + '="' + name + '"]');
        if (el) {
            el.setAttribute('content', content);
        } else {
            el = document.createElement('meta');
            el.setAttribute(attr, name);
            el.setAttribute('content', content);
            document.head.appendChild(el);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
