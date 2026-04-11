<?php

declare(strict_types=1);

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Controller\Story;

use Exception;
use Horde;
use Horde\Core\Config\LegacyMergedConfig;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Browser;
use Horde_Core_Factory_TextFilter;
use Horde_Core_Ui_TagCloud;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Registry;
use Horde_Text_Filter_Text2html;
use Horde_View;
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * PSR-15 controller for viewing a single story.
 *
 * Replaces stories/view.php + Jonah_View_StoryView.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class ViewController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Registry $registry,
        private readonly Horde_Browser $browser,
        private readonly LoggerInterface $logger,
        private readonly LegacyMergedConfig $config,
        private readonly Horde_Core_Factory_TextFilter $textFilter,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $channel_id = $queryParams['channel_id'] ?? null;
        $story_id = $queryParams['id'] ?? null;

        if (!$story_id) {
            try {
                $story_id = $this->driver->getLatestStoryId($channel_id);
            } catch (Exception $e) {
                $this->notification->push(
                    sprintf(_("Error fetching story: %s"), $e->getMessage()),
                    'horde.warning',
                );
                $html = $this->renderChrome(_("Story"), function () {});
                return $this->htmlResponse($html);
            }
        }

        try {
            $story = $this->driver->getStory($story_id, !$this->browser->isRobot());
        } catch (Exception $e) {
            $this->notification->push(
                sprintf(_("Error fetching story: %s"), $e->getMessage()),
                'horde.warning',
            );
            $html = $this->renderChrome(_("Story"), function () {});
            return $this->htmlResponse($html);
        }

        /* Syntax highlighter setup */
        $this->pageOutput->addScriptFile('syntaxhighlighter/scripts/shCore.js', 'horde');
        $this->pageOutput->addScriptFile('syntaxhighlighter/scripts/shAutoloader.js', 'horde');
        $path = $this->urlGenerator->getHordeJsUri() . '/syntaxhighlighter/scripts/';
        $brushes = <<<EOT
                      SyntaxHighlighter.autoloader(
                      'applescript            {$path}shBrushAppleScript.js',
                      'actionscript3 as3      {$path}shBrushAS3.js',
                      'bash shell             {$path}shBrushBash.js',
                      'coldfusion cf          {$path}shBrushColdFusion.js',
                      'cpp c                  {$path}shBrushCpp.js',
                      'c# c-sharp csharp      {$path}shBrushCSharp.js',
                      'css                    {$path}shBrushCss.js',
                      'delphi pascal          {$path}shBrushDelphi.js',
                      'diff patch pas         {$path}shBrushDiff.js',
                      'erl erlang             {$path}shBrushErlang.js',
                      'groovy                 {$path}shBrushGroovy.js',
                      'java                   {$path}shBrushJava.js',
                      'jfx javafx             {$path}shBrushJavaFX.js',
                      'js jscript javascript  {$path}shBrushJScript.js',
                      'perl pl                {$path}shBrushPerl.js',
                      'php                    {$path}shBrushPhp.js',
                      'text plain             {$path}shBrushPlain.js',
                      'py python              {$path}shBrushPython.js',
                      'ruby rails ror rb      {$path}shBrushRuby.js',
                      'sass scss              {$path}shBrushSass.js',
                      'scala                  {$path}shBrushScala.js',
                      'sql                    {$path}shBrushSql.js',
                      'vb vbnet               {$path}shBrushVb.js',
                      'xml xhtml xslt html    {$path}shBrushXml.js'
                    );
            EOT;
        $this->pageOutput->addInlineScript([
            $brushes,
            'SyntaxHighlighter.defaults[\'toolbar\'] = false',
            'SyntaxHighlighter.all()',
        ], true);

        $sh_js_fs = $this->urlGenerator->getHordeJsFs() . '/syntaxhighlighter/styles/';
        $sh_js_uri = $this->urlGenerator->getHordeJsUri()
            . '/syntaxhighlighter/styles/';
        $this->pageOutput->addStylesheet(
            $sh_js_fs . 'shCoreEclipse.css',
            $sh_js_uri . 'shCoreEclipse.css',
        );
        $this->pageOutput->addStylesheet(
            $sh_js_fs . 'shThemeEclipse.css',
            $sh_js_uri . 'shThemeEclipse.css',
        );

        /* Tag cloud for the channel */
        $cloud = new Horde_Core_Ui_TagCloud();
        $allTags = $this->driver->listTagInfo($channel_id);
        foreach ($allTags as $tag_id => $taginfo) {
            $cloud->addElement(
                $taginfo['tag_name'],
                $this->urlGenerator->urlFor('TagSearch', [
                    'tag' => trim($taginfo['tag_name']),
                    'channel_id' => $channel_id,
                ]),
                $taginfo['count'],
            );
        }

        /* Filter story content */
        if (!empty($story['body_type']) && $story['body_type'] === 'text') {
            $story['body'] = $this->textFilter->filter(
                    $story['body'],
                    'text2html',
                    ['parselevel' => Horde_Text_Filter_Text2html::MICRO],
                );
        }

        if (!empty($story['url'])) {
            $story['body'] .= Horde::link(Horde::externalUrl($story['url']))
                . htmlspecialchars($story['url']) . '</a></p>';
        }

        if (empty($story['published_date'])) {
            $story['published_date'] = false;
        }

        $view = new Horde_View(['templatePath' => [
            JONAH_TEMPLATES . '/stories',
            JONAH_TEMPLATES . '/stories/partial',
            JONAH_TEMPLATES . '/stories/layout',
        ]]);
        $view->addHelper('Tag');
        $view->addHelper('Text');
        $view->tagcloud = $cloud->buildHTML();
        $view->story = $story;

        /* Sharing link */
        if ($this->config->get('sharing.allow')) {
            $shareUrl = $this->urlGenerator->urlFor('StoryShare', [
                'id' => $story['id'],
                'channel_id' => $channel_id,
            ]);
            $view->sharelink = Horde::link($shareUrl) . _("Share this story") . '</a>';
        }

        /* Comments */
        if ($this->config->get('comments.allow')) {
            if (!$this->registry->hasMethod('forums/doComments')) {
                $this->logger->error(
                    'User comments are enabled but the forums API is not available.',
                );
            } else {
                try {
                    $comments = $this->registry->call(
                        'forums/doComments',
                        ['jonah', $story_id, 'commentCallback'],
                    );
                } catch (Exception $e) {
                    $this->logger->error('Error fetching comments: {error}', [
                        'error' => $e->getMessage(),
                    ]);
                    $comments = ['threads' => '', 'comments' => ''];
                }
                $view->comments = $comments;
            }
        }

        $html = $this->renderChrome($story['title'] ?? _("Story"), function () use ($view) {
            echo $view->render('view');
        });

        return $this->htmlResponse($html);
    }
}
