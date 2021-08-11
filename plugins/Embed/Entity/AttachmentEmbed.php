<?php
// {{{ License

// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

// }}}

/**
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Stephen Paul Weber
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Embed\Entity;

use App\Core\Entity;
use DateTimeInterface;

/**
 * Table Definition for attachment_embed
 *
 * @author Hugo Sales <hugo@hsal.es>
 * @copyright 2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentEmbed extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $remoteurl_id;
    private int $attachment_id;
    private ?string $title;
    private ?string $description;
    private ?string $provider_name;
    private ?string $provider_url;
    private ?string $author_name;
    private ?string $author_url;
    private ?string $thumbnail_url;
    private \DateTimeInterface $modified;

    public function setRemoteUrlId(int $remoteurl_id): self
    {
        $this->remoteurl_id = $remoteurl_id;
        return $this;
    }

    public function getRemoteUrlId(): int
    {
        return $this->remoteurl_id;
    }

    /**
     * @return int
     */
    public function getAttachmentId(): int
    {
        return $this->attachment_id;
    }

    /**
     * @param int $attachment_id
     */
    public function setAttachmentId(int $attachment_id): void
    {
        $this->attachment_id = $attachment_id;
    }

    public function setProviderName(?string $provider_name): self
    {
        $this->provider_name = $provider_name;
        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->provider_name;
    }

    public function setProviderUrl(?string $provider_url): self
    {
        $this->provider_url = $provider_url;
        return $this;
    }

    public function getProviderUrl(): ?string
    {
        return $this->provider_url;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setAuthorName(?string $author_name): self
    {
        $this->author_name = $author_name;
        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->author_name;
    }

    public function setAuthorUrl(?string $author_url): self
    {
        $this->author_url = $author_url;
        return $this;
    }

    public function getAuthorUrl(): ?string
    {
        return $this->author_url;
    }

    public function setThumbnailUrl(?string $thumbnail_url): self
    {
        $this->thumbnail_url = $thumbnail_url;
        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnail_url;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef()
    {
        return [
            'name'   => 'attachment_embed',
            'fields' => [
                'remoteurl_id'  => ['type' => 'int', 'not null' => true, 'description' => 'Embed for that URL/file'],
                'attachment_id' => ['type' => 'int', 'not null' => true, 'description' => 'Attachment relation, used to show previews'],
                'provider_name' => ['type' => 'text', 'description' => 'name of this Embed provider'],
                'provider_url'  => ['type' => 'text', 'description' => 'URL of this Embed provider'],
                'title'         => ['type' => 'text', 'description' => 'title of Embed resource when available'],
                'author_name'   => ['type' => 'text', 'description' => 'author name for this Embed resource'],
                'author_url'    => ['type' => 'text', 'description' => 'author URL for this Embed resource'],
                'thumbnail_url' => ['type' => 'text', 'description' => 'URL for this Embed resource when applicable (photo, link)'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['remoteurl_id'],
            'foreign keys' => [
                'attachment_embed_remoteurl_id_fkey'  => ['remoteurl', ['remoteurl_id' => 'id']],
                'attachment_embed_attachment_id_fkey' => ['attachment', ['attachment_id' => 'id']],
            ],
        ];
    }
}
