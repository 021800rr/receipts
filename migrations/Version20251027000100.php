<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251027000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Init schema + view + seed';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE EXTENSION IF NOT EXISTS pgcrypto");

        $this->addSql("CREATE TABLE household (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE UNIQUE INDEX uniq_household_name ON household (name)");

        $this->addSql("CREATE TABLE store (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE UNIQUE INDEX uniq_store_name ON store (name)");

        $this->addSql("CREATE TABLE category (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE UNIQUE INDEX uniq_category_name ON category (name)");

        $this->addSql("CREATE TABLE product (id UUID NOT NULL, name VARCHAR(255) NOT NULL, category_id UUID NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE UNIQUE INDEX uniq_product_name ON product (name)");
        $this->addSql("CREATE INDEX idx_product_category ON product (category_id)");

        $this->addSql("CREATE TABLE receipt (id UUID NOT NULL, household_id UUID NOT NULL, store_id UUID NOT NULL, purchase_date DATE NOT NULL, total_amount_grosze BIGINT NOT NULL, notes TEXT DEFAULT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE INDEX idx_receipt_date ON receipt (purchase_date)");
        $this->addSql("CREATE INDEX idx_receipt_household ON receipt (household_id)");
        $this->addSql("CREATE INDEX idx_receipt_store ON receipt (store_id)");

        $this->addSql("CREATE TABLE receipt_line (id UUID NOT NULL, receipt_id UUID NOT NULL, product_id UUID NOT NULL, quantity NUMERIC(10,3) NOT NULL, unit VARCHAR(64) DEFAULT NULL, unit_price_grosze BIGINT NOT NULL, line_total_grosze BIGINT NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE INDEX idx_line_product ON receipt_line (product_id)");
        $this->addSql("CREATE INDEX idx_line_receipt ON receipt_line (receipt_id)");

        $this->addSql("ALTER TABLE product ADD CONSTRAINT FK_product_category FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE receipt ADD CONSTRAINT FK_receipt_household FOREIGN KEY (household_id) REFERENCES household (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE receipt ADD CONSTRAINT FK_receipt_store FOREIGN KEY (store_id) REFERENCES store (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE receipt_line ADD CONSTRAINT FK_line_receipt FOREIGN KEY (receipt_id) REFERENCES receipt (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE receipt_line ADD CONSTRAINT FK_line_product FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE");

        $this->addSql("CREATE OR REPLACE VIEW report_spend AS
            SELECT r.purchase_date, r.household_id, r.store_id, p.category_id, rl.product_id, rl.quantity, rl.line_total_grosze AS amount_grosze
            FROM receipt_line rl
            JOIN receipt r ON r.id = rl.receipt_id
            JOIN product p ON p.id = rl.product_id
        ");

        $this->addSql("INSERT INTO household (id, name) VALUES (gen_random_uuid(), 'Dom A'), (gen_random_uuid(), 'Dom B')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP VIEW IF EXISTS report_spend");
        $this->addSql("DROP TABLE IF EXISTS receipt_line");
        $this->addSql("DROP TABLE IF EXISTS receipt");
        $this->addSql("DROP TABLE IF EXISTS product");
        $this->addSql("DROP TABLE IF EXISTS category");
        $this->addSql("DROP TABLE IF EXISTS store");
        $this->addSql("DROP TABLE IF EXISTS household");
    }
}
