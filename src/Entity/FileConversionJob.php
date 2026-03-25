<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use App\Repository\FileConversionJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FileConversionJobRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_source_file_target_format', columns: ['source_file_id', 'target_format'])]
#[ORM\HasLifecycleCallbacks]
class FileConversionJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    public Uuid $id {
        get {
            return $this->id;
        }
    }

    #[ORM\ManyToOne(targetEntity: File::class, inversedBy: 'fileConversionJobs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?File $sourceFile = null;

    #[ORM\ManyToOne(targetEntity: File::class)]
    private ?File $outputFile = null;

    #[ORM\Column(enumType: FileConversionJobTargetFormatEnum::class)]
    private ?FileConversionJobTargetFormatEnum $targetFormat = null;

    #[ORM\Column(enumType: FileConversionJobStatusEnum::class, options: ['default' => FileConversionJobStatusEnum::PENDING->value])]
    private ?FileConversionJobStatusEnum $status = FileConversionJobStatusEnum::PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private ?int $retryCount = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getSourceFile(): ?File
    {
        return $this->sourceFile;
    }

    public function setSourceFile(?File $sourceFile): self
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    public function getOutputFile(): ?File
    {
        return $this->outputFile;
    }

    public function setOutputFile(?File $outputFile): self
    {
        $this->outputFile = $outputFile;

        return $this;
    }

    public function getTargetFormat(): ?FileConversionJobTargetFormatEnum
    {
        return $this->targetFormat;
    }

    public function setTargetFormat(FileConversionJobTargetFormatEnum $targetFormat): self
    {
        $this->targetFormat = $targetFormat;

        return $this;
    }

    public function getStatus(): ?FileConversionJobStatusEnum
    {
        return $this->status;
    }

    public function setStatus(FileConversionJobStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getRetryCount(): ?int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): self
    {
        $this->retryCount = $retryCount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getProcessingTime(): ?int
    {
        if ($this->completedAt === null || $this->startedAt === null) {
            return null;
        }

        return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
}
