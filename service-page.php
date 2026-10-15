<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function abs_url(string $path): string
{
    return 'https://voronov-art.ru' . $path;
}

$cities = [
    'krasnodar' => [
        'slug' => 'krasnodar',
        'name' => 'Краснодар',
        'in' => 'в Краснодаре',
        'from' => 'Краснодара',
        'region' => 'Краснодарский край',
        'cityPageUrl' => '/krasnodar/krasnodar.html',
        'market' => 'Южный рынок любит быстрые запуски, сезонные офферы и понятную подачу без лишней теории.',
        'demand' => 'строительство, частная медицина, образование, локальные сервисы, девелопмент и e-commerce',
        'focus' => 'В Краснодаре особенно важно быстро обрабатывать мобильные заявки и не терять обращения из рекламы и поиска.',
    ],
    'moskva' => [
        'slug' => 'moskva',
        'name' => 'Москва',
        'in' => 'в Москве',
        'from' => 'Москвы',
        'region' => 'Россия',
        'cityPageUrl' => '/moskva/moskva.html',
        'market' => 'Столичный рынок высококонкурентный: здесь особенно ценят скорость, доверие, кейсы и сильный первый экран.',
        'demand' => 'B2B-услуги, недвижимость, клиники, консалтинг, IT и сложные сервисные проекты',
        'focus' => 'Для Москвы критичны скорость страницы, прозрачная аргументация и понятный путь к заявке без перегруза.',
    ],
    'spb' => [
        'slug' => 'spb',
        'name' => 'Санкт-Петербург',
        'in' => 'в Санкт-Петербурге',
        'from' => 'Санкт-Петербурга',
        'region' => 'Россия',
        'cityPageUrl' => '/spb/spb.html',
        'market' => 'В Петербурге чаще выигрывают аккуратная подача, репутационный контент и более обстоятельная аргументация.',
        'demand' => 'девелопмент, производство, B2B, медицинские услуги, образование и культурные проекты',
        'focus' => 'Для бизнеса Санкт-Петербурга важно сочетать визуальную аккуратность, доверие и удобную навигацию.',
    ],
    'novosibirsk' => [
        'slug' => 'novosibirsk',
        'name' => 'Новосибирск',
        'in' => 'в Новосибирске',
        'from' => 'Новосибирска',
        'region' => 'Россия',
        'cityPageUrl' => '/novosibirsk/novosibirsk.html',
        'market' => 'В Новосибирске сильны рациональные B2B-ниши, логистика, медицина, обучение и производственные компании.',
        'demand' => 'промышленность, оптовые продажи, медицина, обучение, инженерные и логистические услуги',
        'focus' => 'Для Новосибирска хорошо работают страницы, где оффер, расчёт выгоды и сценарий обращения понятны с первого экрана.',
    ],
    'ekaterinburg' => [
        'slug' => 'ekaterinburg',
        'name' => 'Екатеринбург',
        'in' => 'в Екатеринбурге',
        'from' => 'Екатеринбурга',
        'region' => 'Россия',
        'cityPageUrl' => '/ekaterinburg/ekaterinburg.html',
        'market' => 'Рынок Урала ценит конкретику, сроки, понятную экономику проекта и отсутствие лишнего шума в подаче.',
        'demand' => 'производство, стройка, B2B-сервисы, клиники, оптовые компании и промышленный сектор',
        'focus' => 'Для бизнеса Екатеринбурга особенно важны ясные преимущества, быстрый доступ к контактам и честная структура страницы.',
    ],
];

$services = [
    'lending' => [
        'slug' => 'lending',
        'navName' => 'Лендинг',
        'fullName' => 'Лендинг под ключ',
        'shortLead' => 'Одностраничный сайт под рекламу, оффер или локальную услугу.',
        'summary' => 'Лендинг для быстрого запуска рекламы и сбора заявок.',
        'pageTitle' => static fn(array $city): string => 'Лендинг под ключ ' . $city['in'] . ' — разработка landing page | Студия Воронова',
        'metaDescription' => static fn(array $city): string => 'Разработка лендинга под ключ ' . $city['in'] . ': структура под заявки, адаптивная вёрстка, аналитика и CRM. Без шаблонных SEO-дублей и серых методов.',
        'heroTitle' => static fn(array $city): string => 'Лендинг под ключ ' . $city['in'],
        'introTitle' => static fn(array $city): string => 'Когда бизнесу нужен лендинг ' . $city['in'],
        'introParagraphs' => static fn(array $city): array => [
            'Лендинг нужен, когда у бизнеса есть один основной оффер: услуга, акция, запуск продукта, отдельное направление или рекламная кампания. Формат помогает не распылять внимание посетителя и доводить его до конкретного действия.',
            'Для компаний из ' . $city['from'] . ' это хороший вариант, если нужно быстро протестировать спрос, собрать поток лидов из рекламы и не тратить время на большой многостраничный проект.',
            $city['market'] . ' ' . $city['focus'],
        ],
        'includesTitle' => 'Что входит в разработку лендинга',
        'includes' => static fn(array $city): array => [
            [
                'title' => 'Оффер и структура',
                'text' => 'Разбираем продукт, конкурентов и запросы аудитории ' . $city['in'] . ', чтобы страница не выглядела шаблонной и сразу вела к заявке.',
            ],
            [
                'title' => 'Прототип и тексты',
                'text' => 'Собираем смысловые блоки, усиливаем преимущества, закрываем возражения и формируем понятный сценарий от первого экрана до формы.',
            ],
            [
                'title' => 'Адаптив и аналитика',
                'text' => 'Делаем мобильную вёрстку, подключаем Метрику, цели, формы, CRM или Telegram, чтобы лендинг реально приносил обращения.',
            ],
        ],
        'stages' => [
            ['title' => 'Бриф и оценка', 'time' => '1–2 дня', 'text' => 'Уточняем оффер, сегменты аудитории и сценарий заявки. На выходе — смета и понятный план запуска.'],
            ['title' => 'Прототип', 'time' => '2–4 дня', 'text' => 'Показываем структуру экранов, логику блоков и точки захвата лида до дизайна.'],
            ['title' => 'Дизайн и вёрстка', 'time' => '4–7 дней', 'text' => 'Собираем адаптивную посадочную, проверяем первый экран, формы, CTA и скорость загрузки.'],
            ['title' => 'Запуск и поддержка', 'time' => 'после релиза', 'text' => 'Подключаем аналитику, тестируем формы и помогаем с первым циклом рекламы или SEO-доработок.'],
        ],
        'faq' => static fn(array $city): array => [
            [
                'question' => 'Сколько стоит лендинг под ключ ' . $city['in'] . '?',
                'answer' => 'Цена зависит от сложности оффера, количества экранов, дизайна и интеграций. Точную оценку даём после короткого брифа, обычно в течение 1–2 рабочих дней.',
            ],
            [
                'question' => 'Сколько времени занимает запуск?',
                'answer' => 'Типовой срок для лендинга — от 7 до 10 рабочих дней. Если нужны нестандартные анимации, сложные формы или интеграции, срок может увеличиться.',
            ],
            [
                'question' => 'Можно ли продвигать лендинг в поиске, а не только в рекламе?',
                'answer' => 'Да, если страница не сведена к одному экрану и содержит полезную структуру: смысловые блоки, FAQ, контент под реальные запросы, перелинковку и корректную микроразметку.',
            ],
            [
                'question' => 'Вы работаете только офлайн?',
                'answer' => 'Нет. Мы ведём проекты удалённо по договору: бриф, демонстрации, правки, запуск и передача доступов проходят онлайн без фиктивных адресов и “виртуальных офисов”.',
            ],
        ],
        'blogLinks' => [
            ['/blog/skolko-stoit-sayt-pod-kluch.html', 'Сколько стоит сайт под ключ'],
            ['/blog/sajt-dlya-biznesa.html', 'Какой сайт нужен бизнесу'],
            ['/blog/tg-bot-dlya-biznesa.html', 'Когда бизнесу нужен Telegram-бот'],
        ],
    ],
    'korporativnyj-sajt' => [
        'slug' => 'korporativnyj-sajt',
        'navName' => 'Корпоративный сайт',
        'fullName' => 'Корпоративный сайт под ключ',
        'shortLead' => 'Многостраничный сайт компании с услугами, кейсами, формами и SEO-структурой.',
        'summary' => 'Сайт компании для доверия, SEO и работы с несколькими услугами.',
        'pageTitle' => static fn(array $city): string => 'Корпоративный сайт под ключ ' . $city['in'] . ' — разработка сайта компании | Студия Воронова',
        'metaDescription' => static fn(array $city): string => 'Разработка корпоративного сайта ' . $city['in'] . ': структура услуг, кейсы, блог, формы, SEO-подготовка и аналитика. Для компаний, которым нужен не шаблон, а рабочий инструмент продаж.',
        'heroTitle' => static fn(array $city): string => 'Корпоративный сайт под ключ ' . $city['in'],
        'introTitle' => static fn(array $city): string => 'Когда компании нужен корпоративный сайт ' . $city['in'],
        'introParagraphs' => static fn(array $city): array => [
            'Корпоративный сайт подходит бизнесу, которому нужно презентовать компанию, показать направления, закрыть вопросы по доверию и получать заявки по нескольким услугам сразу.',
            'Это оптимальный формат для компаний из ' . $city['from'] . ', если лендинга уже мало: нужно добавить разделы, кейсы, FAQ, блог, новости, вакансии, каталог услуг или сложную навигацию.',
            $city['market'] . ' В таких проектах особенно важны понятная архитектура, сильные страницы услуг и корректная SEO-основа без искусственного дублирования.',
        ],
        'includesTitle' => 'Что входит в разработку корпоративного сайта',
        'includes' => static fn(array $city): array => [
            [
                'title' => 'Архитектура и разделы',
                'text' => 'Проектируем структуру услуг, посадочных страниц, FAQ, кейсов и дополнительных разделов под реальные сценарии пользователей ' . $city['in'] . '.',
            ],
            [
                'title' => 'Дизайн и доверие',
                'text' => 'Собираем визуальную подачу, которая усиливает бренд: преимущества, сертификаты, портфолио, отзывы, команда, контакты и доказательства экспертизы.',
            ],
            [
                'title' => 'CMS, формы и аналитика',
                'text' => 'Подключаем формы, Метрику, CRM, блог, карточки услуг, перелинковку и дальнейшую основу под контентное SEO.',
            ],
        ],
        'stages' => [
            ['title' => 'Исследование и карта сайта', 'time' => '2–4 дня', 'text' => 'Фиксируем направления бизнеса, структуру разделов, приоритетные услуги и нужные сценарии заявок.'],
            ['title' => 'Прототип и тексты', 'time' => '4–7 дней', 'text' => 'Собираем страницы верхнего уровня, показываем навигацию, посадочные блоки и смысловую логику.'],
            ['title' => 'Дизайн и сборка', 'time' => '1–2 недели', 'text' => 'Верстаем шаблоны страниц, подключаем CMS, формы, блог, аналитику и технические SEO-элементы.'],
            ['title' => 'Наполнение и запуск', 'time' => 'после сборки', 'text' => 'Проверяем индексацию, каноникалы, карту сайта, формы и базовую перелинковку перед выходом в индекс.'],
        ],
        'faq' => static fn(array $city): array => [
            [
                'question' => 'Чем корпоративный сайт отличается от лендинга?',
                'answer' => 'Лендинг продаёт один оффер, а корпоративный сайт раскрывает компанию целиком: направления, услуги, кейсы, статьи, вакансии и точки доверия. Он лучше подходит для SEO и долгого цикла сделки.',
            ],
            [
                'question' => 'На какой CMS вы делаете такие сайты?',
                'answer' => 'Обычно используем WordPress, если нужна удобная админка и дальнейшее наполнение. Для более кастомных задач возможна разработка на чистой вёрстке с интеграцией нужной CMS.',
            ],
            [
                'question' => 'Можно ли перенести существующий сайт без потери SEO?',
                'answer' => 'Да. Для этого заранее готовим карту редиректов, сохраняем полезные URL, проверяем каноникалы, обновляем sitemap и постепенно переносим сильные страницы в новую структуру.',
            ],
            [
                'question' => 'Вы работаете с компаниями из ' . $city['from'] . ' удалённо?',
                'answer' => 'Да. Все этапы проекта можно вести онлайн: бриф, созвоны, правки, презентации, согласование структуры и запуск. Это безопаснее, чем имитировать локальный офис там, где его нет.',
            ],
        ],
        'blogLinks' => [
            ['/blog/kak-vybrat-web-studiyu.html', 'Как выбрать веб-студию'],
            ['/blog/tilda-ili-wordpress.html', 'Tilda или WordPress'],
            ['/blog/zachem-nuzhno-seo.html', 'Зачем бизнесу SEO'],
        ],
    ],
    'internet-magazin' => [
        'slug' => 'internet-magazin',
        'navName' => 'Интернет-магазин',
        'fullName' => 'Интернет-магазин под ключ',
        'shortLead' => 'E-commerce проект с каталогом, корзиной, оплатой и SEO-структурой.',
        'summary' => 'Интернет-магазин для каталога, заказов, оплаты и роста из поиска.',
        'pageTitle' => static fn(array $city): string => 'Интернет-магазин под ключ ' . $city['in'] . ' — разработка e-commerce | Студия Воронова',
        'metaDescription' => static fn(array $city): string => 'Разработка интернет-магазина ' . $city['in'] . ': каталог, корзина, фильтры, оплата, доставка, CRM и SEO-подготовка. Создаём магазины без серых дублей и пустых страниц.',
        'heroTitle' => static fn(array $city): string => 'Интернет-магазин под ключ ' . $city['in'],
        'introTitle' => static fn(array $city): string => 'Когда бизнесу нужен интернет-магазин ' . $city['in'],
        'introParagraphs' => static fn(array $city): array => [
            'Интернет-магазин нужен, когда бизнесу уже мало заявки “оставьте телефон”. Нужны каталог, карточки товаров, корзина, оплата, доставка, фильтрация и удобное управление ассортиментом.',
            'Для компаний из ' . $city['from'] . ' такой формат особенно полезен, если продажи идут в нескольких городах, есть регулярный ассортимент, SEO-потенциал по категориям и задачи по интеграции с CRM, складом или учётом.',
            $city['market'] . ' Для e-commerce мы сразу думаем не только о запуске, но и о дальнейшем росте: категории, фильтры, карточки, коммерческие блоки и удобная индексация.',
        ],
        'includesTitle' => 'Что входит в разработку интернет-магазина',
        'includes' => static fn(array $city): array => [
            [
                'title' => 'Каталог и структура',
                'text' => 'Проектируем категории, фильтры, карточки товаров, поиск и логику каталога так, чтобы магазин был понятен клиентам ' . $city['in'] . ' и не терял SEO-потенциал.',
            ],
            [
                'title' => 'Корзина и интеграции',
                'text' => 'Подключаем корзину, оплату, доставку, уведомления, CRM, аналитику, выгрузки и другие системы, которые нужны для нормальной торговли.',
            ],
            [
                'title' => 'Коммерческие и SEO-блоки',
                'text' => 'Добавляем отзывы, характеристики, FAQ, блоки доверия, тексты категорий, хлебные крошки и микроразметку для роста из органики.',
            ],
        ],
        'stages' => [
            ['title' => 'Анализ ассортимента', 'time' => '2–4 дня', 'text' => 'Определяем логику каталога, типы товаров, фильтры, интеграции и сценарии оформления заказа.'],
            ['title' => 'Прототип и UX', 'time' => '4–7 дней', 'text' => 'Показываем основные страницы магазина: главную, категории, карточку товара, корзину и оформление заказа.'],
            ['title' => 'Сборка и интеграции', 'time' => '2–3 недели', 'text' => 'Подключаем CMS, WooCommerce или другой стек, настраиваем оплату, доставку, CRM и аналитику.'],
            ['title' => 'Тестирование и запуск', 'time' => 'перед релизом', 'text' => 'Проверяем сценарии покупки, уведомления, структуру URL, индексацию категорий и коммерческие факторы.'],
        ],
        'faq' => static fn(array $city): array => [
            [
                'question' => 'На чём вы делаете интернет-магазины?',
                'answer' => 'Чаще всего используем WordPress + WooCommerce, если нужен понятный административный интерфейс и гибкость. Для простых витрин возможна и Tilda, если её ограничений достаточно.',
            ],
            [
                'question' => 'Можно ли сразу подготовить магазин к SEO?',
                'answer' => 'Да. Мы заранее продумываем категории, фильтры, карточки товаров, хлебные крошки, коммерческие блоки, canonical, sitemap и индексацию, чтобы магазин не рос хаотично.',
            ],
            [
                'question' => 'Подключаете оплату и доставку?',
                'answer' => 'Да. Настраиваем корзину, оплату, интеграции с доставкой, уведомления, CRM и базовую аналитику e-commerce.',
            ],
            [
                'question' => 'Вы работаете с магазинами из ' . $city['from'] . ' только локально?',
                'answer' => 'Нет. Разработка, тестирование, передача каталога и запуск могут идти полностью онлайн. Мы не используем фиктивные адреса, а честно показываем, что работаем удалённо по России.',
            ],
        ],
        'blogLinks' => [
            ['/blog/sajt-kak-onlajn-magazin.html', 'Когда бизнесу нужен сайт как онлайн-магазин'],
            ['/blog/zachem-nuzhno-seo.html', 'Зачем бизнесу SEO'],
            ['/blog/skolko-stoit-sayt-pod-kluch.html', 'Сколько стоит сайт под ключ'],
        ],
    ],
];

$citySlug = isset($_GET['city']) ? (string) $_GET['city'] : '';
$serviceSlug = isset($_GET['service']) ? (string) $_GET['service'] : '';

if (!isset($cities[$citySlug], $services[$serviceSlug])) {
    http_response_code(404);
    readfile(__DIR__ . DIRECTORY_SEPARATOR . '404.html');
    exit;
}

$city = $cities[$citySlug];
$service = $services[$serviceSlug];

$currentPath = '/' . $city['slug'] . '/' . $service['slug'] . '/';
$currentUrl = abs_url($currentPath);
$title = $service['pageTitle']($city);
$description = $service['metaDescription']($city);
$heroTitle = $service['heroTitle']($city);
$introTitle = $service['introTitle']($city);
$introParagraphs = $service['introParagraphs']($city);
$includeCards = $service['includes']($city);
$faqItems = $service['faq']($city);
$otherCities = array_filter($cities, static fn(array $item): bool => $item['slug'] !== $city['slug']);
$otherServices = array_filter($services, static fn(array $item): bool => $item['slug'] !== $service['slug']);

$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'Студия веб-разработки Воронова',
    'url' => 'https://voronov-art.ru/',
    'logo' => 'https://voronov-art.ru/img/logo_2.png',
    'telephone' => '+79604987864',
    'email' => 'info@voronov-art.ru',
    'description' => 'Создание сайтов под ключ, SEO-подготовка, контекстная реклама, аналитика и CRM. Работаем удалённо по всей России.',
    'sameAs' => [
        'https://vk.com/voronov_web',
        'https://t.me/voronov_tg',
        'https://max.ru/u/f9LHodD0cOI0qhzsscrWqC_PtkCNF2djBcLcMsjcftt2tyt-GsrMhg4onLM',
    ],
];

$serviceSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => $heroTitle,
    'serviceType' => $service['fullName'],
    'description' => $description,
    'url' => $currentUrl,
    'provider' => [
        '@type' => 'Organization',
        'name' => 'Студия веб-разработки Воронова',
        'url' => 'https://voronov-art.ru/',
        'telephone' => '+79604987864',
        'email' => 'info@voronov-art.ru',
    ],
    'areaServed' => [
        [
            '@type' => 'Country',
            'name' => 'Россия',
        ],
        [
            '@type' => 'City',
            'name' => $city['name'],
        ],
    ],
    'offers' => [
        '@type' => 'Offer',
        'availability' => 'https://schema.org/InStock',
        'url' => $currentUrl,
    ],
];

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Главная',
            'item' => 'https://voronov-art.ru/',
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $city['name'],
            'item' => abs_url($city['cityPageUrl']),
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $service['fullName'],
            'item' => $currentUrl,
        ],
    ],
];

$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(
        static fn(array $item): array => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['answer'],
            ],
        ],
        $faqItems
    ),
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="author" content="Студия Воронова">
    <meta name="robots" content="index, follow, max-image-preview:large">

    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($currentUrl) ?>">
    <meta property="og:image" content="https://voronov-art.ru/img/logo_2.png">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="Студия Воронова">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="https://voronov-art.ru/img/logo_2.png">

    <link rel="canonical" href="<?= e($currentUrl) ?>">
    <link rel="alternate" type="application/rss+xml" title="Блог студии Воронова — RSS" href="https://voronov-art.ru/blog/rss.xml">

    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="shortcut icon" href="/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/favicon/site.webmanifest">
    <meta name="apple-mobile-web-app-title" content="Студия Воронова">
    <meta name="msapplication-TileColor" content="#4a6fa5">
    <meta name="theme-color" content="#4a6fa5">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://mc.yandex.ru">
    <link rel="dns-prefetch" href="https://mc.yandex.ru">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Jura:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="/style/main.css">
    <style>
        .service-page-breadcrumbs {
            max-width: 1200px;
            margin: 20px auto 0;
            padding: 0 20px;
            font-size: 14px;
            color: #5a6d8a;
        }

        .service-page-breadcrumbs a {
            color: #4a6fa5;
        }

        .service-page-lead {
            max-width: 860px;
            margin: 18px auto 0;
            font-size: clamp(17px, 2.2vw, 22px);
            line-height: 1.65;
            opacity: .94;
            text-align: center;
        }

        .service-page-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 28px;
        }

        .service-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 999px;
            border: 1px solid rgba(74,111,165,0.25);
            background: rgba(255,255,255,0.78);
            color: #22324d;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .service-page-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(74,111,165,0.15);
            border-color: rgba(74,111,165,0.45);
        }

        .service-note,
        .service-chip-grid,
        .service-related-grid,
        .faq-stack {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .service-note-card {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 28px;
            border-radius: 24px;
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(74,111,165,0.12);
            box-shadow: 0 20px 45px rgba(39,63,103,0.08);
            line-height: 1.75;
        }

        .service-note-card strong {
            color: #203252;
        }

        .service-chip-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .service-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255,255,255,0.76);
            border: 1px solid rgba(74,111,165,0.15);
            color: #22324d;
            font-size: 14px;
            font-weight: 600;
        }

        .service-related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .service-related-card {
            padding: 24px;
            border-radius: 24px;
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(74,111,165,0.12);
            box-shadow: 0 14px 30px rgba(39,63,103,0.08);
        }

        .service-related-card h3 {
            font-size: 22px;
            margin-bottom: 12px;
            color: #203252;
        }

        .service-related-card p {
            line-height: 1.7;
            color: #34435f;
            margin-bottom: 14px;
        }

        .service-related-card a {
            color: #4a6fa5;
            font-weight: 700;
        }

        .faq-stack details {
            margin-bottom: 16px;
            background: rgba(255,255,255,0.85);
            padding: 22px 24px;
            border-radius: 18px;
            border: 1px solid rgba(74,111,165,0.12);
            box-shadow: 0 14px 30px rgba(39,63,103,0.06);
        }

        .faq-stack summary {
            cursor: pointer;
            font-weight: 700;
            font-size: 18px;
            color: #203252;
        }

        .faq-stack p {
            margin-top: 16px;
            line-height: 1.7;
            color: #34435f;
        }

        @media (max-width: 768px) {
            .service-page-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .service-page-link,
            .project-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JFKDPNHH6E"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-JFKDPNHH6E');
    </script>

    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a);
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=106882746', 'ym');

        ym(106882746, 'init', {
            ssr:true,
            webvisor:true,
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            ecommerce:"dataLayer"
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/106882746" style="position:absolute; left:-9999px;" alt=""></div></noscript>

    <script type="application/ld+json"><?= json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
    <script type="application/ld+json"><?= json_encode($serviceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
    <script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
    <script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
</head>
<body>
    <header class="header-new">
        <a href="/" class="logo-area" style="text-decoration:none;color:inherit;">
            <img src="/img/logo_2.png" alt="Студия Воронова">
            <div class="studio-name">
                <span>Воронов</span> | Веб-студия
            </div>
        </a>
        <div class="contact-area">
            <a href="tel:+79604987864" class="phone-number">+7 (960) 498 78-64</a>
            <a href="#contacts" class="project-btn" style="text-decoration:none;display:inline-block;line-height:inherit;">Обсудить проект</a>
        </div>
    </header>

    <nav class="service-page-breadcrumbs" aria-label="Хлебные крошки">
        <a href="/">Главная</a>
        <span> / </span>
        <a href="<?= e($city['cityPageUrl']) ?>"><?= e($city['name']) ?></a>
        <span> / </span>
        <span><?= e($service['fullName']) ?></span>
    </nav>

    <main>
        <section class="blok_1">
            <div class="blok_1_zg">
                <h1><?= e($heroTitle) ?></h1>
                <p class="service-page-lead">
                    <?= e($service['shortLead']) ?> Делаем страницы для компаний из <?= e($city['from']) ?> с реальной пользой для людей и безопасной структурой для поиска: без пустых зеркал, без фиктивных офисов и без серых приёмов.
                </p>
                <div class="service-page-actions">
                    <a href="#contacts" class="project-btn" style="text-decoration:none;display:inline-block;line-height:inherit;">Оставить заявку</a>
                    <a href="<?= e($city['cityPageUrl']) ?>" class="service-page-link">Все услуги <?= e($city['in']) ?></a>
                    <a href="/work.html" class="service-page-link">Посмотреть кейсы</a>
                </div>
            </div>
        </section>

        <section class="blok_about">
            <h2 class="blok_2_nm"><?= e($introTitle) ?></h2>
            <div class="blok_2_zg">Полезная страница под реальный спрос, а не технический клон под замену города</div>
            <div class="blok_3_tg" style="max-width:900px;margin:0 auto 30px;text-align:left;">
                <?php foreach ($introParagraphs as $paragraph): ?>
                    <p style="margin-bottom:15px;line-height:1.75;"><?= e($paragraph) ?></p>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="blok_3">
            <h2 class="blok_3_nm"><?= e($service['includesTitle']) ?></h2>
            <div class="blok_3_zg">От исследования и структуры до запуска, аналитики и поддержки</div>
            <div class="blok_3_ln">
                <?php foreach ($includeCards as $index => $card): ?>
                    <article class="ln_<?= $index + 1 ?>">
                        <h3 class="ln_b_1"><?= e($card['title']) ?></h3>
                        <p class="ln_b_2"><?= e($card['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="blok_2">
            <h2 class="blok_2_nm">Этапы проекта</h2>
            <div class="blok_2_zg">Прозрачный процесс, понятные сроки и контроль на каждом шаге</div>
            <div>
                <?php foreach ($service['stages'] as $index => $stage): ?>
                    <article class="blok_2_<?= $index + 1 ?>">
                        <div class="nm">
                            <div class="step-number"><?= $index + 1 ?></div>
                            <h3><?= e($stage['title']) ?></h3>
                            <p><?= e($stage['time']) ?></p>
                        </div>
                        <div class="nm_txt"><?= e($stage['text']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="blok_5" style="background-color:#f5f9ff;">
            <div style="max-width:1200px;margin:0 auto;padding:40px 20px;">
                <h2 class="blok_2_nm">Как готовим страницу к органике без риска санкций</h2>
                <div class="blok_2_zg">Белая SEO-логика вместо механического копирования и doorway-структуры</div>
                <div class="service-note-card">
                    <p style="margin-bottom:14px;">
                        <strong>Что мы делаем:</strong> пишем уникальные title и description, собираем полезную структуру, добавляем FAQ, хлебные крошки,
                        self-canonical, schema.org и понятную перелинковку между услугами и городами.
                    </p>
                    <p style="margin-bottom:14px;">
                        <strong>Чего мы не делаем:</strong> не плодим пустые страницы с заменой одного города, не используем скрытый текст, не заявляем
                        вымышленные офисы и не мешаем индексации дублями.
                    </p>
                    <p>
                        Это особенно важно для запросов <?= e($city['in']) ?>, потому что локальный интент поисковики уже давно умеют распознавать.
                        Страница должна быть полезной человеку, а не только роботу.
                    </p>
                </div>
            </div>
        </section>

        <section class="blok_4">
            <h2 class="blok_2_nm">Ещё полезные страницы для бизнеса <?= e($city['in']) ?></h2>
            <div class="blok_2_zg">Перелинковка внутри кластера помогает и пользователю, и индексации</div>
            <div class="service-related-grid">
                <?php foreach ($otherServices as $relatedService): ?>
                    <article class="service-related-card">
                        <h3><?= e($relatedService['fullName']) ?> <?= e($city['in']) ?></h3>
                        <p><?= e($relatedService['summary']) ?></p>
                        <a href="/<?= e($city['slug']) ?>/<?= e($relatedService['slug']) ?>/">Открыть страницу →</a>
                    </article>
                <?php endforeach; ?>
                <article class="service-related-card">
                    <h3>Все услуги <?= e($city['in']) ?></h3>
                    <p>Городская страница со сводной структурой по разработке сайтов, направлениям и кейсам для бизнеса из <?= e($city['from']) ?>.</p>
                    <a href="<?= e($city['cityPageUrl']) ?>">Перейти к странице города →</a>
                </article>
                <article class="service-related-card">
                    <h3>Портфолио студии</h3>
                    <p>Реальные проекты, которыми можно подтвердить уровень дизайна, сборки, структуры и подхода к запуску.</p>
                    <a href="/work.html">Смотреть кейсы →</a>
                </article>
            </div>
        </section>

        <section class="blok_5">
            <div style="max-width:1200px;margin:0 auto;padding:40px 20px;">
                <h2 class="blok_2_nm">Этот формат делаем и в других городах</h2>
                <div class="blok_2_zg">Страницы связаны между собой, но каждая собрана под свой локальный интент</div>
                <div class="service-chip-grid">
                    <?php foreach ($otherCities as $relatedCity): ?>
                        <a class="service-chip" href="/<?= e($relatedCity['slug']) ?>/<?= e($service['slug']) ?>/"><?= e($service['fullName']) ?> <?= e($relatedCity['in']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="blok_6">
            <h2 class="blok_6_nm">Полезные материалы по теме</h2>
            <div class="blok_6_txt">Статьи, которые помогают принять решение до старта проекта</div>
            <div class="service-related-grid">
                <?php foreach ($service['blogLinks'] as [$url, $label]): ?>
                    <article class="service-related-card">
                        <h3><?= e($label) ?></h3>
                        <p>Короткий материал по теме, который помогает разобраться в формате проекта, бюджете и следующем шаге.</p>
                        <a href="<?= e($url) ?>">Читать статью →</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="blok_5" style="background-color:#f5f9ff;">
            <div style="max-width:900px;margin:0 auto;padding:40px 20px;">
                <h2 class="blok_2_nm">Частые вопросы</h2>
                <div class="faq-stack">
                    <?php foreach ($faqItems as $item): ?>
                        <details>
                            <summary><?= e($item['question']) ?></summary>
                            <p><?= e($item['answer']) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="contacts" class="blok_contacts">
            <h2 class="blok_2_nm">Обсудить проект <?= e($city['in']) ?></h2>
            <div class="contacts_content">
                <div class="contact_info_left">
                    <div class="contact_info">
                        <a href="tel:+79604987864" class="contact_item_link">
                            <div class="contact_item">
                                <div class="contact_icon">📞</div>
                                <span class="contact_label">Телефон:</span>
                                <span class="contact_value">+7 (960) 498-78-64</span>
                            </div>
                        </a>
                        <a href="mailto:info@voronov-art.ru" class="contact_item_link">
                            <div class="contact_item">
                                <div class="contact_icon">✉️</div>
                                <span class="contact_label">Email:</span>
                                <span class="contact_value">info@voronov-art.ru</span>
                            </div>
                        </a>
                        <a href="https://t.me/voronov_tg" target="_blank" rel="noopener noreferrer" class="contact_item_link">
                            <div class="contact_item">
                                <div class="contact_icon">📱</div>
                                <span class="contact_label">Telegram:</span>
                                <span class="contact_value">@voronov_tg</span>
                            </div>
                        </a>
                        <div class="contact_item">
                            <div class="contact_icon">🌍</div>
                            <span class="contact_label">Формат работы:</span>
                            <span class="contact_value">Удалённо по договору, без фиктивных офисов</span>
                        </div>
                        <div class="contact_item">
                            <div class="contact_icon">🧭</div>
                            <span class="contact_label">Ниши:</span>
                            <span class="contact_value"><?= e($city['demand']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="contact_form_right">
                    <div class="contact_form_card">
                        <h3 class="form_title"><?= e($service['fullName']) ?></h3>
                        <p class="form_subtitle">Ответим в течение рабочего дня</p>
                        <form id="contactFormMain" class="contact_form">
                            <div class="form_group">
                                <input type="text" id="name_main" name="name" placeholder="Ваше имя *" required>
                            </div>
                            <div class="form_group">
                                <input type="tel" id="phone_main" name="phone" placeholder="+7 (___) ___-__-__ *" required>
                            </div>
                            <div class="form_group">
                                <input type="email" id="email_main" name="email" placeholder="Email">
                            </div>
                            <div class="form_group">
                                <textarea id="message_main" name="message" rows="3" placeholder="Коротко опишите задачу и направление бизнеса"></textarea>
                            </div>
                            <div class="form_consent" style="display:flex;align-items:flex-start;gap:8px;font-size:13px;line-height:1.5;color:#555;margin:12px 0 16px;">
                                <input type="checkbox" id="consent_main" name="consent" required style="margin-top:3px;flex-shrink:0;">
                                <label for="consent_main" style="cursor:pointer;">
                                    Я даю согласие на обработку персональных данных в соответствии с <a href="/privacy-policy.html" target="_blank" rel="noopener" style="color:#4a6fa5;">Политикой обработки ПДн</a>
                                </label>
                            </div>
                            <button type="submit" class="form_submit_btn">Отправить заявку</button>
                        </form>
                        <div id="successMessageMain" class="success_message" style="display:none;">
                            <div class="success_icon">✓</div>
                            <h4>Спасибо за заявку!</h4>
                            <p>Мы свяжемся с вами в ближайшее время</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="cookie-consent" class="cookie-consent" style="display: none;">
        <div class="cookie-content">
            <div class="cookie-text">
                <strong>🍪 Мы используем файлы cookie</strong>
                <p>Сайт использует cookie для корректной работы, анализа трафика (Яндекс.Метрика) и запоминания ваших предпочтений. Нажимая «Принять», вы даёте согласие на использование cookie в соответствии с <a href="/privacy-policy.html" target="_blank">Политикой обработки персональных данных</a>.</p>
            </div>
            <div class="cookie-buttons">
                <button id="cookie-accept" class="cookie-btn accept">Принять</button>
                <button id="cookie-decline" class="cookie-btn decline">Отклонить</button>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-inner">
            <nav class="footer-nav" aria-label="Навигация в футере">
                <a href="/">Главная</a>
                <a href="<?= e($city['cityPageUrl']) ?>"><?= e($city['name']) ?></a>
                <a href="/work.html">Портфолио</a>
                <a href="/blog/">Блог</a>
                <a href="#contacts">Контакты</a>
                <a href="/privacy-policy.html">Политика</a>
            </nav>
            <nav class="footer-nav" aria-label="Направления разработки" style="margin-top:8px;">
                <?php foreach ($services as $footerService): ?>
                    <a href="/<?= e($city['slug']) ?>/<?= e($footerService['slug']) ?>/"><?= e($footerService['fullName']) ?></a>
                <?php endforeach; ?>
            </nav>
            <p class="footer-copy">© <span id="current-year">2026</span> Студия Воронова — создание и продвижение сайтов под ключ по всей России. <a href="/privacy-policy.html#requisites" style="color:inherit;text-decoration:underline;">Реквизиты исполнителя</a>.</p>
        </div>
    </footer>

    <script src="/style/js/main.js"></script>
    <script src="/style/js/cookie-manager.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var yearElement = document.getElementById('current-year');
            if (yearElement) {
                yearElement.textContent = new Date().getFullYear();
            }
        });
    </script>
</body>
</html>
