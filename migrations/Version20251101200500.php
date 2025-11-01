<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101200500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set position NOT NULL and drop created_at from receipt_line';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE receipt_line ALTER COLUMN position SET NOT NULL');
        $this->addSql('ALTER TABLE receipt_line DROP COLUMN created_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE receipt_line ADD COLUMN created_at TIMESTAMP(6) WITHOUT TIME ZONE NULL');
        $this->addSql('ALTER TABLE receipt_line ALTER COLUMN position DROP NOT NULL');
    }
}

