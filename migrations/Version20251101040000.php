<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rec line created at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE receipt_line ADD COLUMN created_at timestamptz DEFAULT now() NOT NULL");
        $this->addSql("CREATE INDEX idx_receipt_line_created_at ON receipt_line (created_at DESC)");
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_receipt_line_created_at');
        $this->addSql('ALTER TABLE receipt_line DROP COLUMN created_at');
    }
}

