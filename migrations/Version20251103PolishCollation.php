<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251103PolishCollation extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Polska kolacja dla sortowania po name na tabelach household/store/category/product (ICU jeśli dostępne, w przeciwnym razie glibc pl_PL.utf8). Tworzy indeksy pod ORDER BY.';
    }

    public function up(Schema $schema): void
    {
        $hasIcu = false;
        try {
            $hasIcu = (bool) $this->connection->fetchOne("SELECT EXISTS (SELECT 1 FROM pg_collation WHERE collprovider = 'i' LIMIT 1)");
        } catch (Exception $e) {
            $hasIcu = false;
        }

        $collationName = $hasIcu ? 'pl-x-icu' : 'pl_PL.utf8';

        if ($hasIcu) {
            $this->addSql("CREATE COLLATION IF NOT EXISTS \"pl-x-icu\" (provider = icu, locale = 'pl', deterministic = true)");
        } else {
            $this->addSql("CREATE COLLATION IF NOT EXISTS \"pl_PL.utf8\" (locale = 'pl_PL.utf8')");
        }

        $this->addSql(sprintf('ALTER TABLE household ALTER COLUMN name TYPE VARCHAR(255) COLLATE "%s"', $collationName));
        $this->addSql(sprintf('ALTER TABLE store     ALTER COLUMN name TYPE VARCHAR(255) COLLATE "%s"', $collationName));
        $this->addSql(sprintf('ALTER TABLE category  ALTER COLUMN name TYPE VARCHAR(255) COLLATE "%s"', $collationName));
        $this->addSql(sprintf('ALTER TABLE product   ALTER COLUMN name TYPE VARCHAR(255) COLLATE "%s"', $collationName));

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_household_name_pl ON household (name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_store_name_pl     ON store (name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_name_pl  ON category (name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_product_name_pl   ON product (name)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Ta migracja działa tylko na PostgreSQL.');

        $this->addSql('ALTER TABLE household ALTER COLUMN name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE store     ALTER COLUMN name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE category  ALTER COLUMN name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE product   ALTER COLUMN name TYPE VARCHAR(255)');

        $this->addSql('DROP INDEX IF EXISTS idx_household_name_pl');
        $this->addSql('DROP INDEX IF EXISTS idx_store_name_pl');
        $this->addSql('DROP INDEX IF EXISTS idx_category_name_pl');
        $this->addSql('DROP INDEX IF EXISTS idx_product_name_pl');
    }
}
