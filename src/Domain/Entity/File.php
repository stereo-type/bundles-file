<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @copyright  2025 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[ORM\Entity]
#[ORM\Table(name: 'file_bundle_files')]
#[ORM\Index(name: 'idx_contenthash', columns: ['contenthash'])]
#[ORM\Index(name: 'idx_pathnamehash', columns: ['pathnamehash'])]
#[ORM\Index(name: 'idx_context_component_filearea_itemid', columns: ['contextid', 'component', 'filearea', 'itemid'])]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 40, nullable: false)]
    private string $contenthash;

    #[ORM\Column(type: Types::STRING, length: 40, nullable: false)]
    private string $pathnamehash;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private int $contextid;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: false)]
    private string $component;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: false)]
    private string $filearea;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private int $itemid;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $filepath;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $filename;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $userid = null;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private int $filesize;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $mimetype = null;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private int $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $license = null;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private int $timecreated;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private int $timemodified;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private int $sortorder;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $referencefileid = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getContenthash(): string
    {
        return $this->contenthash;
    }

    public function setContenthash(string $contenthash): self
    {
        $this->contenthash = $contenthash;

        return $this;
    }

    public function getPathnamehash(): string
    {
        return $this->pathnamehash;
    }

    public function setPathnamehash(string $pathnamehash): self
    {
        $this->pathnamehash = $pathnamehash;

        return $this;
    }

    public function getContextid(): int
    {
        return $this->contextid;
    }

    public function setContextid(int $contextid): self
    {
        $this->contextid = $contextid;

        return $this;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setComponent(string $component): self
    {
        $this->component = $component;

        return $this;
    }

    public function getFilearea(): string
    {
        return $this->filearea;
    }

    public function setFilearea(string $filearea): self
    {
        $this->filearea = $filearea;

        return $this;
    }

    public function getItemid(): int
    {
        return $this->itemid;
    }

    public function setItemid(int $itemid): self
    {
        $this->itemid = $itemid;

        return $this;
    }

    public function getFilepath(): string
    {
        return $this->filepath;
    }

    public function setFilepath(string $filepath): self
    {
        $this->filepath = $filepath;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getUserid(): ?int
    {
        return $this->userid;
    }

    public function setUserid(?int $userid): self
    {
        $this->userid = $userid;

        return $this;
    }

    public function getFilesize(): int
    {
        return $this->filesize;
    }

    public function setFilesize(int $filesize): self
    {
        $this->filesize = $filesize;

        return $this;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function setLicense(?string $license): self
    {
        $this->license = $license;

        return $this;
    }

    public function getTimecreated(): int
    {
        return $this->timecreated;
    }

    public function setTimecreated(int $timecreated): self
    {
        $this->timecreated = $timecreated;

        return $this;
    }

    public function getTimemodified(): int
    {
        return $this->timemodified;
    }

    public function setTimemodified(int $timemodified): self
    {
        $this->timemodified = $timemodified;

        return $this;
    }

    public function getSortorder(): int
    {
        return $this->sortorder;
    }

    public function setSortorder(int $sortorder): self
    {
        $this->sortorder = $sortorder;

        return $this;
    }

    public function getReferencefileid(): ?int
    {
        return $this->referencefileid;
    }

    public function setReferencefileid(?int $referencefileid): self
    {
        $this->referencefileid = $referencefileid;

        return $this;
    }
}
