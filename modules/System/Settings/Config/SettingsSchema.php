<?php

declare(strict_types=1);

namespace Modules\System\Settings\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Схема "нормальных" настроек (как в админках):
 * - фиксированный набор полей
 * - дефолтные значения
 * - валидация
 * - типы (string/text/bool/int/select)
 *
 * Хранение в БД: table `settings` (key/value)
 */
class SettingsSchema extends BaseConfig
{
    /**
     * Группы и поля для формы настроек.
     *
     * Поля:
     * - key: string (ключ в БД)
     * - label: string
     * - type: string (string|text|bool|int|select)
     * - default: mixed
     * - rules: string (CI4 validation rules, применяются к значению)
     * - help: string|null
     * - options: array<string,string> (для select)
     * - autoload: bool (ставить autoload=1 в БД)
     */
    public array $groups = [
        'general' => [
            'label'  => 'General',
            'fields' => [
                [
                    'key'      => 'site.title',
                    'label'    => 'Site title',
                    'type'     => 'string',
                    'default'  => 'My Site',
                    'rules'    => 'required|max_length[120]',
                    'help'     => 'Отображается в заголовке страницы и в шапке.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'site.tagline',
                    'label'    => 'Tagline',
                    'type'     => 'string',
                    'default'  => '',
                    'rules'    => 'permit_empty|max_length[160]',
                    'help'     => 'Короткое описание/слоган.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'site.homepage_title_mode',
                    'label'    => 'Title format',
                    'type'     => 'select',
                    'default'  => 'page_first',
                    'rules'    => 'required|in_list[page_first,site_first,only_page,only_site]',
                    'options'  => [
                        'page_first' => 'Page — Site',
                        'site_first' => 'Site — Page',
                        'only_page'  => 'Only page title',
                        'only_site'  => 'Only site title',
                    ],
                    'help'     => 'Формат формирования тайтла в layout.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'ui.items_per_page',
                    'label'    => 'Items per page',
                    'type'     => 'int',
                    'default'  => 25,
                    'rules'    => 'required|is_natural_no_zero|greater_than_equal_to[1]|less_than_equal_to[500]',
                    'help'     => 'Значение по умолчанию для пагинации в списках.',
                    'autoload' => true,
                ],
            ],
        ],

        'seo' => [
            'label'  => 'SEO',
            'fields' => [
                [
                    'key'      => 'seo.meta_description',
                    'label'    => 'Meta description',
                    'type'     => 'text',
                    'default'  => '',
                    'rules'    => 'permit_empty|max_length[320]',
                    'help'     => 'meta description для главной (если не переопределяется страницей).',
                    'autoload' => true,
                ],
                [
                    'key'      => 'seo.meta_keywords',
                    'label'    => 'Meta keywords',
                    'type'     => 'string',
                    'default'  => '',
                    'rules'    => 'permit_empty|max_length[255]',
                    'help'     => 'Если используете (часто не нужно).',
                    'autoload' => true,
                ],
                [
                    'key'      => 'seo.robots',
                    'label'    => 'Robots',
                    'type'     => 'select',
                    'default'  => 'index,follow',
                    'rules'    => 'required|in_list[index,follow,noindex,nofollow,noindex,nofollow,index,nofollow,noindex,follow]',
                    'options'  => [
                        'index,follow'   => 'index,follow',
                        'noindex,follow' => 'noindex,follow',
                        'index,nofollow' => 'index,nofollow',
                        'noindex,nofollow'=> 'noindex,nofollow',
                    ],
                    'help'     => 'meta robots по умолчанию.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'seo.og_image_url',
                    'label'    => 'OpenGraph image URL',
                    'type'     => 'string',
                    'default'  => '',
                    'rules'    => 'permit_empty|max_length[255]',
                    'help'     => 'URL картинки для соцсетей (если не задаётся страницей).',
                    'autoload' => true,
                ],
            ],
        ],

        'mail' => [
            'label'  => 'Email',
            'fields' => [
                [
                    'key'      => 'mail.from_name',
                    'label'    => 'From name',
                    'type'     => 'string',
                    'default'  => 'Admin',
                    'rules'    => 'required|max_length[120]',
                    'help'     => 'Имя отправителя по умолчанию.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'mail.from_email',
                    'label'    => 'From email',
                    'type'     => 'string',
                    'default'  => 'no-reply@example.com',
                    'rules'    => 'required|valid_email|max_length[191]',
                    'help'     => 'Email отправителя по умолчанию.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'mail.reply_to',
                    'label'    => 'Reply-To',
                    'type'     => 'string',
                    'default'  => '',
                    'rules'    => 'permit_empty|valid_email|max_length[191]',
                    'help'     => 'Опционально.',
                    'autoload' => true,
                ],
            ],
        ],

        'system' => [
            'label'  => 'System',
            'fields' => [
                [
                    'key'      => 'app.timezone',
                    'label'    => 'Timezone',
                    'type'     => 'string',
                    'default'  => 'Europe/Kyiv',
                    'rules'    => 'required|max_length[64]',
                    'help'     => 'Например: Europe/Kyiv.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'app.locale',
                    'label'    => 'Default locale',
                    'type'     => 'string',
                    'default'  => 'en',
                    'rules'    => 'required|max_length[10]',
                    'help'     => 'Например: uk, en, ru.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'assets.cdn_url',
                    'label'    => 'CDN base URL',
                    'type'     => 'string',
                    'default'  => '',
                    'rules'    => 'permit_empty|max_length[255]',
                    'help'     => 'Если используете CDN (например https://cdn.example.com).',
                    'autoload' => true,
                ],
            ],
        ],

        'maintenance' => [
            'label'  => 'Maintenance',
            'fields' => [
                [
                    'key'      => 'maintenance.enabled',
                    'label'    => 'Maintenance mode',
                    'type'     => 'bool',
                    'default'  => false,
                    'rules'    => 'permit_empty',
                    'help'     => 'Включить режим обслуживания.',
                    'autoload' => true,
                ],
                [
                    'key'      => 'maintenance.message',
                    'label'    => 'Maintenance message',
                    'type'     => 'text',
                    'default'  => 'Site is under maintenance.',
                    'rules'    => 'permit_empty|max_length[2000]',
                    'help'     => 'Текст сообщения для пользователей.',
                    'autoload' => true,
                ],
            ],
        ],
    ];

    /**
     * Какие ключи удобно шарить в layout (через Renderer->share()).
     * map: setting_key => shared_var_name
     */
    public array $shareMap = [
        'site.title'          => 'siteTitle',
        'site.tagline'        => 'siteTagline',
        'seo.meta_description'=> 'metaDescription',
        'seo.meta_keywords'   => 'metaKeywords',
        'seo.robots'          => 'metaRobots',
        'seo.og_image_url'    => 'ogImageUrl',
        'maintenance.enabled' => 'maintenanceEnabled',
        'maintenance.message' => 'maintenanceMessage',
    ];
}
