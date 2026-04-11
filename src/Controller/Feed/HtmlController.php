<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Controller\Feed;

use Exception;
use Horde;
use Horde\Jonah\Service\ChannelRenderer;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Registry;
use Horde_Util;
use Horde_View;
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 controller for HTML feed delivery.
 *
 * Replaces delivery/html.php + Jonah_View_DeliveryHtml.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class HtmlController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly ChannelRenderer $renderer,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Registry $registry,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        /* Accept criteria from the REST dispatcher (delivery/index.php) */
        $criteria = $request->getAttribute('criteria');
        if (!$criteria) {
            $criteria = [
                'feed' => $queryParams['channel_id'] ?? null,
                'format' => $queryParams['format'] ?? null,
            ];
        }

        /**
         * Load the template definitions from config/templates.php.
         */
        $result = $this->registry->loadConfigFile('templates.php', 'templates', 'jonah');
        $templates = $result->config['templates'];

        if (empty($criteria['channel_format'])) {
            $criteria['channel_format'] = key($templates);
        }

        $options = [];
        foreach ($templates as $key => $info) {
            $selected = ($key === $criteria['channel_format']) ? ' selected="selected"' : '';
            $options[] = '<option value="' . $key . '"' . $selected . '>'
                . $info['name'] . '</option>';
        }

        if (empty($criteria['channel_id']) && !empty($criteria['feed'])) {
            $criteria['channel_id'] = $this->driver->getChannelId($criteria['feed']);
        }

        if (empty($criteria['channel_id'])) {
            $this->notification->push(_("No valid feed name or ID requested."), 'horde.error');
        }

        $view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/delivery']);
        $view->url = Horde::selfUrl();
        $view->session = Horde_Util::formInput();
        $view->channel_id = $criteria['channel_id'] ?? '';
        $view->format = $criteria['channel_format'];
        $view->options = $options;

        try {
            $channel = $this->driver->getChannel($criteria['channel_id']);
            $view->channel_name = $channel['channel_name'];
        } catch (Exception $e) {
            $view->channel_name = '';
        }

        try {
            $view->stories = $this->renderer->render(
                (int) $criteria['channel_id'],
                $criteria['channel_format'],
                $templates,
            );
        } catch (Exception $e) {
            $view->stories = '';
        }

        $html = $this->renderChrome(_("Feed"), function () use ($view) {
            echo $view->render('html');
        });

        return $this->htmlResponse($html);
    }
}
