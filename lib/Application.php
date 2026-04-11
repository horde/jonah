<?php

/**
 * Jonah application API.
 *
 * @package Jonah
 */

if (!defined('JONAH_BASE')) {
    define('JONAH_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(JONAH_BASE . '/config/horde.local.php')) {
        include JONAH_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', JONAH_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Jonah_Application extends Horde_Registry_Application
{
    public $version = '1.0.0-beta1';

    /**
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Jonah_Driver', 'Jonah_Factory_Driver', 'create');

        $injector = $GLOBALS['injector'];

        /* PSR-3 logger — bind NullLogger if no concrete logger is registered */
        if (!$injector->has(Psr\Log\LoggerInterface::class)) {
            $injector->bindClosure(
                Psr\Log\LoggerInterface::class,
                function () {
                    return new Psr\Log\NullLogger();
                },
            );
        }

        /* PSR-4 services */
        $injector->bindClosure(
            Horde\Jonah\Service\PermissionChecker::class,
            function ($injector) {
                return new Horde\Jonah\Service\PermissionChecker(
                    $injector->getInstance('Horde_Perms'),
                    $injector->getInstance('Horde_Registry'),
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\Service\StoryConfig::class,
            function () {
                return new Horde\Jonah\Service\StoryConfig(
                    $GLOBALS['conf']['news']['story_types'] ?? [],
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\Service\FeedFetcher::class,
            function ($injector) {
                $responseFactory = new Horde\Http\ResponseFactory();
                $streamFactory = new Horde\Http\StreamFactory();
                $client = new Horde\Http\Client\Curl(
                    $responseFactory,
                    $streamFactory,
                    new Horde\Http\Client\Options(),
                );

                return new Horde\Jonah\Service\FeedFetcher(
                    $client,
                    new Horde\Http\RequestFactory(),
                    $injector->getInstance(Psr\Log\LoggerInterface::class),
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\Service\StoryMailer::class,
            function ($injector) {
                return new Horde\Jonah\Service\StoryMailer(
                    $injector->getInstance(Psr\Log\LoggerInterface::class),
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\Service\ChannelRenderer::class,
            function ($injector) {
                return new Horde\Jonah\Service\ChannelRenderer(
                    $injector->getInstance('Jonah_Driver'),
                    $injector->getInstance(Psr\Log\LoggerInterface::class),
                    $injector->getInstance(Horde\Jonah\Service\FilesystemPathHelper::class),
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\Service\FilesystemPathHelper::class,
            function ($injector) {
                $registry = $injector->getInstance('Horde_Registry');

                return new Horde\Jonah\Service\FilesystemPathHelper(
                    JONAH_BASE,
                    $registry->get('jsuri', 'horde'),
                    $registry->get('jsfs', 'horde'),
                    $registry->get('themesuri', 'horde'),
                    $registry->get('themesuri', 'jonah'),
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\Service\UrlSigner::class,
            function () {
                return new Horde\Jonah\Service\UrlSigner(
                    $GLOBALS['conf']['secret_key'] ?? '',
                    (int) ($GLOBALS['conf']['urls']['hmac_lifetime'] ?? 30),
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\Service\UrlGenerator::class,
            function ($injector) {
                $mapper = new Horde\Routes\Mapper();
                require JONAH_BASE . '/config/routes.php';
                if (file_exists(JONAH_BASE . '/config/routes.local.php')) {
                    include JONAH_BASE . '/config/routes.local.php';
                }
                $registry = $injector->getInstance('Horde_Registry');
                $webroot = $registry->get('webroot', 'jonah');

                return new Horde\Jonah\Service\UrlGenerator(
                    $mapper,
                    $webroot,
                );
            },
        );

        $injector->bindClosure(
            Horde\Jonah\View\ViewFactory::class,
            function ($injector) {
                return new Horde\Jonah\View\ViewFactory(
                    $injector->getInstance(Horde\Jonah\Service\UrlGenerator::class),
                    $injector->getInstance(Horde\Jonah\Service\FilesystemPathHelper::class),
                );
            },
        );
    }

    /**
     */
    protected function _init()
    {
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $url = Horde::url('delivery/rss.php', true, -1)
                ->add('channel_id', $channel_id);
            if ($tag_id = Horde_Util::getFormData('tag_id')) {
                $url = $url->add('tag_id', $tag_id);
            }

            $GLOBALS['page_output']->addLinkTag([
                'href' => $url,
                'title' => 'RSS 0.91',
            ]);
        }

        /* For now, autoloading the Content_* classes depend on there being a
          * registry entry for the 'content' application that contains at least
          * the fileroot entry. */
        $GLOBALS['injector']->getInstance('Horde_Autoloader')
            ->addClassPathMapper(
                new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/')
            );

    }

    /**
     */
    public function perms()
    {
        $perms = [
            'admin' => [
                'title' => _("Administrator"),
            ],
            'news' => [
                'title' => _("News"),
            ],
            'news:channels' => [
                'title' => _("Channels"),
            ],
        ];

        /* Loop through internal channels and add them to the perms
         * titles. */
        $channels = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannels();

        foreach ($channels as $channel) {
            $perms['news:channels:' . $channel['channel_id']] = [
                'title' => $channel['channel_name'],
            ];
        }

        return $perms;
    }

    /**
     */
    public function menu($menu)
    {
        /* If authorized, show admin links. */
        if (Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
            $menu->addArray([
                'icon' => 'jonah.png',
                'text' => _("_Feeds"),
                'url' => Horde::url('channels/index.php'),
            ]);
            $menu->addArray([
                'icon' => 'new.png',
                'text' => _("New Feed"),
                'url' => Horde::url('channels/edit.php'),
            ]);
        }

        /* If viewing a channel, show new story links if authorized */
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $news = $GLOBALS['injector']->getInstance('Jonah_Driver');
            try {
                $channel = $news->getChannel($channel_id);
            } catch (Exception $e) {
                return;
            }
            if (Jonah::checkPermissions('channels', Horde_Perms::EDIT, [['channel_id' => (int) $channel_id]])) {
                $menu->addArray([
                    'icon' => 'new.png',
                    'text' => _("_New Story"),
                    'url' => Horde::url('stories/edit.php')->add('channel_id', (int) $channel_id),
                ]);
            }
        }
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(
        Horde_Tree_Renderer_Base $tree,
        $parent = null,
        array $params = []
    ) {
        if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
            return;
        }

        $url = Horde::url('stories/');
        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');

        try {
            $channels = $driver->getChannels('internal');
        } catch (Jonah_Exception $e) {
            return;
        }

        $channels = Jonah::checkPermissions('channels', Horde_Perms::SHOW, $channels);
        $story_img = Horde_Themes::img('editstory.png');

        foreach ($channels as $channel) {
            $tree->addNode([
                'id' => $parent . $channel['channel_id'],
                'parent' => $parent,
                'label' => $channel['channel_name'],
                'expanded' => false,
                'params' => [
                    'icon' => $story_img,
                    'url' => $url->add('channel_id', $channel['channel_id']),
                ],
            ]);
        }
    }

}
