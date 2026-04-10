<?php

use Horde\Jonah\Service\FeedFetcher;
use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Service\StoryConfig;
use Horde\Jonah\StoryOrder;

/**
 * Jonah Base Class.
 *
 * @deprecated Use the individual service classes in Horde\Jonah\Service\ and
 *             the StoryOrder enum instead.
 *
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Eric Rechlin <eric@hpcalc.org>
 *
 * @package Jonah
 */
class Jonah
{
    /**
     * @deprecated Use StoryOrder::Published->value
     */
    public const ORDER_PUBLISHED = 0;

    /**
     * @deprecated Use StoryOrder::Read->value
     */
    public const ORDER_READ = 1;

    /**
     * @deprecated Use StoryOrder::Comments->value
     */
    public const ORDER_COMMENTS = 2;

    /**
     * Obtain the list of stories from the passed in URI.
     *
     * @deprecated Use Horde\Jonah\Service\FeedFetcher::fetch() instead.
     *
     * @param string $url  The url to get the list of the channel's stories.
     *
     * @return array{body: string, charset?: string}
     */
    public static function readURL($url)
    {
        global $injector;

        return $injector->getInstance(FeedFetcher::class)->fetch($url);
    }

    /**
     * @deprecated Use Horde\Jonah\Service\PermissionChecker::check() instead.
     *
     * @param string $filter       The type of channel
     * @param integer $permission  Horde_Perms:: constant
     * @param mixed $in            Items to filter
     *
     * @return mixed  An array of results or a single boolean
     */
    public static function checkPermissions($filter, $permission = Horde_Perms::READ, $in = null)
    {
        global $injector;

        return $injector->getInstance(PermissionChecker::class)
            ->check($filter, $permission, $in);
    }

    /**
     * Returns an array of configured body types from Jonah's $conf array.
     *
     * @deprecated Use Horde\Jonah\Service\StoryConfig::getBodyTypes() instead.
     *
     * @return array  An array of body types.
     */
    public static function getBodyTypes()
    {
        global $injector;

        return $injector->getInstance(StoryConfig::class)->getBodyTypes();
    }

    /**
     * Tries to figure out a default body type.
     *
     * @deprecated Use Horde\Jonah\Service\StoryConfig::getDefaultBodyType() instead.
     *
     * @return string|null  A default type.
     */
    public static function getDefaultBodyType()
    {
        global $injector;

        return $injector->getInstance(StoryConfig::class)->getDefaultBodyType();
    }

}
