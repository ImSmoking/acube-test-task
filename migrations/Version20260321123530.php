<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321123530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id UUID NOT NULL, original_filename VARCHAR(255) NOT NULL, stored_filename VARCHAR(255) NOT NULL, path VARCHAR(1000) NOT NULL, mime_type VARCHAR(100) NOT NULL, extension VARCHAR(5) NOT NULL, size BIGINT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE file_conversion_job (id UUID NOT NULL, target_format VARCHAR(255) NOT NULL, status VARCHAR(255) DEFAULT \'pending\' NOT NULL, error_message TEXT DEFAULT NULL, retry_count SMALLINT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, source_file_id UUID NOT NULL, output_file_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DF92AB3CDA14C104 ON file_conversion_job (source_file_id)');
        $this->addSql('CREATE INDEX IDX_DF92AB3C4AC9FE8A ON file_conversion_job (output_file_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_source_file_target_format ON file_conversion_job (source_file_id, target_format)');
        $this->addSql('ALTER TABLE file_conversion_job ADD CONSTRAINT FK_DF92AB3CDA14C104 FOREIGN KEY (source_file_id) REFERENCES file (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE file_conversion_job ADD CONSTRAINT FK_DF92AB3C4AC9FE8A FOREIGN KEY (output_file_id) REFERENCES file (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_conversion_job DROP CONSTRAINT FK_DF92AB3CDA14C104');
        $this->addSql('ALTER TABLE file_conversion_job DROP CONSTRAINT FK_DF92AB3C4AC9FE8A');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE file_conversion_job');
    }
}
