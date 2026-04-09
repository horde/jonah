<?php

/**
 * Default template for rendering individual stories.
 * Expects:
 *    ->published_date
 *    ->title
 *    ->tags
 *    ->description
 *    ->body
 */

echo $this->contentTag(
    'h1',
    $this->contentTag(
        'span',
        $this->story['published_date'],
        ['class' => 'storyDate']
    ) . $this->escape($this->story['title']),
    ['class' => 'header']
);

echo $this->contentTag('div', _("Tags:") . implode(', ', $this->story['tags']), ['class' => 'storyTags']);
echo $this->contentTag('div', $this->escape($this->story['description']), ['class' => 'storySubtitle']);
// body is already escaped in the View class.
echo $this->contentTag('div', $this->story['body'], ['class' => 'storyBody']);
