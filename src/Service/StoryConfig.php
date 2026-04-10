<?php

declare(strict_types=1);

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Service;

/**
 * Story body type configuration for Jonah.
 *
 * Replaces the static Jonah::getBodyTypes() and Jonah::getDefaultBodyType()
 * methods with an injectable service.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class StoryConfig
{
    /**
     * Cached body types.
     *
     * @var array<string, string>
     */
    private array $types = [];

    /**
     * @param array $storyTypes  Configured story types from
     *                           $conf['news']['story_types'].
     */
    public function __construct(
        private readonly array $storyTypes,
    ) {}

    /**
     * Returns the configured body types.
     *
     * @return array<string, string>  Keyed by type slug, value is display name.
     */
    public function getBodyTypes(): array
    {
        if (!empty($this->types)) {
            return $this->types;
        }

        if (in_array('richtext', $this->storyTypes, true)) {
            $this->types['richtext'] = _("Rich Text");
        }

        /* Text is inserted by default if no other body type has been enabled. */
        if (in_array('text', $this->storyTypes, true) || empty($this->types)) {
            $this->types['text'] = _("Text");
        }

        return $this->types;
    }

    /**
     * Returns a default body type to fall back on when none is specified.
     */
    public function getDefaultBodyType(): ?string
    {
        $types = $this->getBodyTypes();

        if (isset($types['text'])) {
            return 'text';
        }
        if (isset($types['richtext'])) {
            return 'richtext';
        }

        return array_key_first($types);
    }
}
