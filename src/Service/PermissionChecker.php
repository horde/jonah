<?php

declare(strict_types=1);

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Service;

use Horde_Perms;
use Horde_Perms_Base;
use Horde_Registry;

/**
 * Channel and story permission checking for Jonah.
 *
 * Replaces the static Jonah::checkPermissions() method with an
 * injectable service.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class PermissionChecker
{
    public function __construct(
        private readonly Horde_Perms_Base $perms,
        private readonly Horde_Registry $registry,
    ) {}

    /**
     * Check whether the current user has the requested permission.
     *
     * @param string      $filter      Permission filter type ('channels' or a
     *                                 permission string like 'jonah:news').
     * @param int         $permission  Horde_Perms:: constant.
     * @param array|null  $in          Items to filter. For 'channels', an array
     *                                 of channel arrays with 'channel_id' keys.
     *
     * @return array|bool  Filtered array when $in is an array, boolean otherwise.
     */
    public function check(
        string $filter,
        int $permission = Horde_Perms::READ,
        ?array $in = null,
    ): array|bool {
        if ($this->registry->isAdmin([
            'permission' => 'jonah:admin',
            'permlevel' => $permission,
        ])) {
            if (empty($in)) {
                return is_array($in) ? [] : true;
            }
            return $in;
        }

        $auth = $this->registry->getAuth();

        if ($filter === 'channels') {
            /* If no jonah:news permission is defined at all, allow access
             * (default-open — admin can restrict later). */
            if (!$this->perms->exists('jonah:news')) {
                return empty($in) ? (is_array($in) ? [] : true) : $in;
            }

            $out = [];
            foreach (($in ?? []) as $key => $val) {
                $id = is_array($val) ? $val['channel_id'] : $val;
                if ($this->perms->hasPermission('jonah:news', $auth, $permission)
                    || $this->perms->hasPermission(
                        'jonah:news:' . $id,
                        $auth,
                        $permission,
                    )
                ) {
                    $out[$key] = $in[$key];
                }
            }
            return $out;
        }

        return $this->perms->hasPermission($filter, $auth, Horde_Perms::EDIT);
    }
}
