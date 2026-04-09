<?php

/**
 * Adds autoincrement flags
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Jonah
 */
class JonahUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('jonah_channels', 'channel_id', 'autoincrementKey');
        if (in_array('jonah_channels_seq', $this->tables())) {
            $this->dropTable('jonah_channels_seq');
        }
        $this->changeColumn('jonah_stories', 'story_id', 'autoincrementKey');
        if (in_array('jonah_stories_seq', $this->tables())) {
            $this->dropTable('jonah_stories_seq');
        }
        $this->changeColumn('jonah_tags', 'tag_id', 'autoincrementKey');
        if (in_array('jonah_tags_seq', $this->tables())) {
            $this->dropTable('jonah_tags_seq');
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('jonah_channels', 'channel_id', 'integer', ['null' => false]);
        $this->changeColumn('jonah_stories', 'story_id', 'integer', ['null' => false]);
        $this->changeColumn('jonah_tags', 'tag_id', 'integer', ['null' => false]);
    }

}
