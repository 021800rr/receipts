<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable position column to receipt_line for manual backfill';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE receipt_line ADD COLUMN position INT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE receipt_line DROP COLUMN position');
    }
}

