CREATE TABLE IF NOT EXISTS works (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    category VARCHAR(100) DEFAULT '',
    description TEXT DEFAULT '',
    full_description TEXT DEFAULT '',
    image VARCHAR(500) DEFAULT '',
    url VARCHAR(500) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Примеры данных на основе существующих работ
INSERT INTO works (title, slug, category, description, full_description, image, url, sort_order) VALUES
('Prime Estate', 'prime-estate', 'Сайт недвижимости', 'Сайт агентства недвижимости с каталогом объектов', 'Разработка полноценного сайта агентства недвижимости Prime Estate. Каталог объектов, фильтрация, подробные карточки объектов, форма обратной связи.', '/work/Prime_Estate/img/preview.png', '/work/Prime_Estate/', 1),
('Объектив', 'objektiv', 'Фотостудия', 'Сайт фотостудии с портфолио работ', 'Разработка сайта фотостудии "Объектив". Галерея работ, информация об услугах, онлайн-запись на съёмку.', '/work/objektiv/img/preview.png', '/work/objektiv/', 2),
('КиноСет', 'kinoset', 'Развлечения', 'Сайт кинотеатра с афишей и расписанием', 'Разработка сайта кинотеатра "КиноСет". Афиша фильмов, расписание сеансов, онлайн-бронирование.', '/work/kinoset/img/preview.png', '/work/kinoset/', 3),
('Крас Доставка', 'kras-dostavka', 'Доставка', 'Сервис доставки еды по Краснодару', 'Разработка сайта сервиса доставки еды "Крас Доставка". Каталог блюд, корзина, оформление заказа.', '/work/kras-dostavka/img/preview.png', '/work/kras-dostavka/', 4),
('КропРемАвто', 'krop-rem-avto', 'Автосервис', 'Сайт автосервиса с записью на обслуживание', 'Разработка сайта автосервиса "КропРемАвто". Перечень услуг, прайс-лист, онлайн-запись.', '/work/krop_rem_avto/img/preview.png', '/work/krop_rem_avto/', 5),
('SiteKRD', 'sitekrd', 'Веб-студия', 'Лендинг веб-студии', 'Разработка лендинга веб-студии SiteKRD. Услуги, портфолио, контакты.', '/work/sitekrd/img/preview.png', '/work/sitekrd/', 6);
