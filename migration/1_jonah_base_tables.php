<?php

/**
 * Create jonah base tables as of
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Jonah
 */
class JonahBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('jonah_channels', $tableList)) {
            $t = $this->createTable('jonah_channels', ['autoincrementKey' => false]);
            $t->column('channel_id', 'integer', ['null' => false]);
            $t->column('channel_slug', 'string', ['limit' => 64, 'null' => false]);
            $t->column('channel_name', 'string', ['limit' => 255, 'null' => false]);
            $t->column('channel_type', 'integer');
            $t->column('channel_full_feed', 'integer', ['null' => false, 'default' => 0]);
            $t->column('channel_desc', 'string', ['limit' => 255]);
            $t->column('channel_interval', 'integer');
            $t->column('channel_url', 'string', ['limit' => 255]);
            $t->column('channel_link', 'string', ['limit' => 255]);
            $t->column('channel_page_link', 'string', ['limit' => 255]);
            $t->column('channel_story_url', 'string', ['limit' => 255]);
            $t->column('channel_img', 'string', ['limit' => 255]);
            $t->column('channel_updated', 'integer');
            $t->primaryKey(['channel_id']);
            $t->end();

            $this->addIndex('jonah_channels', ['channel_type']);
        }

        if (!in_array('jonah_stories', $tableList)) {
            $t = $this->createTable('jonah_stories', ['autoincrementKey' => false]);
            $t->column('story_id', 'integer', ['null' => false]);
            $t->column('channel_id', 'integer', ['null' => false]);
            $t->column('story_author', 'string', ['limit' => 255, 'null' => false]);
            $t->column('story_title', 'string', ['limit' => 255, 'null' => false]);
            $t->column('story_desc', 'text');
            $t->column('story_body_type', 'string', ['limit' => 255, 'null' => false]);
            $t->column('story_body', 'text');
            $t->column('story_url', 'string', ['limit' => 255]);
            $t->column('story_permalink', 'string', ['limit' => 255]);
            $t->column('story_published', 'integer');
            $t->column('story_updated', 'integer', ['null' => false]);
            $t->column('story_read', 'integer', ['null' => false]);
            $t->primaryKey(['story_id']);
            $t->end();

            $this->addIndex('jonah_stories', ['channel_id']);
            $this->addIndex('jonah_stories', ['story_published']);
            $this->addIndex('jonah_stories', ['story_url']);
        }

        if (!in_array('jonah_stories_tags', $tableList)) {
            $t = $this->createTable('jonah_stories_tags', ['autoincrementKey' => false]);
            $t->column('story_id', 'integer', ['null' => false]);
            $t->column('channel_id', 'integer', ['null' => false]);
            $t->column('tag_id', 'integer', ['null' => false]);
            $t->primaryKey(['story_id', 'channel_id', 'tag_id']);
            $t->end();
        }

        if (!in_array('jonah_tags', $tableList)) {
            $t = $this->createTable('jonah_tags', ['autoincrementKey' => false]);
            $t->column('tag_id', 'integer', ['null' => false]);
            $t->column('tag_name', 'string', ['limit' => 255, 'null' => false]);
            $t->primaryKey(['tag_id']);
            $t->end();
        }

    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('jonah_channels');
        $this->dropTable('jonah_stories');
        $this->dropTable('jonah_stories_tags');
        $this->dropTable('jonah_tags');
    }

}
