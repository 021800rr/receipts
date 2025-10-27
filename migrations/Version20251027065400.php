<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027065400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS report_spend CASCADE');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE category ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN category.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE household ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN household.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE product ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE product ALTER category_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN product.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN product.category_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE receipt ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE receipt ALTER household_id TYPE UUID');
        $this->addSql('ALTER TABLE receipt ALTER store_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN receipt.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN receipt.household_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN receipt.store_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE receipt_line DROP CONSTRAINT fk_line_receipt');
        $this->addSql('ALTER TABLE receipt_line ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE receipt_line ALTER receipt_id TYPE UUID');
        $this->addSql('ALTER TABLE receipt_line ALTER product_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN receipt_line.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN receipt_line.receipt_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN receipt_line.product_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE receipt_line ADD CONSTRAINT FK_476F8F7A2B5CA896 FOREIGN KEY (receipt_id) REFERENCES receipt (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE store ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN store.id IS \'(DC2Type:uuid)\'');

        $this->addSql(<<<'SQL'
CREATE VIEW report_spend AS
SELECT
    r.purchase_date,
    r.household_id,
    r.store_id,
    p.category_id,
    rl.product_id,
    rl.quantity,
    rl.line_total_grosze AS amount_grosze
FROM receipt_line rl
JOIN receipt r ON r.id = rl.receipt_id
JOIN product p ON p.id = rl.product_id;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS report_spend CASCADE');
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE store ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN store.id IS NULL');
        $this->addSql('ALTER TABLE household ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN household.id IS NULL');
        $this->addSql('ALTER TABLE product ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE product ALTER category_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN product.id IS NULL');
        $this->addSql('COMMENT ON COLUMN product.category_id IS NULL');
        $this->addSql('ALTER TABLE receipt ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE receipt ALTER household_id TYPE UUID');
        $this->addSql('ALTER TABLE receipt ALTER store_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN receipt.id IS NULL');
        $this->addSql('COMMENT ON COLUMN receipt.household_id IS NULL');
        $this->addSql('COMMENT ON COLUMN receipt.store_id IS NULL');
        $this->addSql('ALTER TABLE category ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN category.id IS NULL');
        $this->addSql('ALTER TABLE receipt_line DROP CONSTRAINT FK_476F8F7A2B5CA896');
        $this->addSql('ALTER TABLE receipt_line ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE receipt_line ALTER receipt_id TYPE UUID');
        $this->addSql('ALTER TABLE receipt_line ALTER product_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN receipt_line.id IS NULL');
        $this->addSql('COMMENT ON COLUMN receipt_line.receipt_id IS NULL');
        $this->addSql('COMMENT ON COLUMN receipt_line.product_id IS NULL');
        $this->addSql('ALTER TABLE receipt_line ADD CONSTRAINT fk_line_receipt FOREIGN KEY (receipt_id) REFERENCES receipt (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql(<<<'SQL'
CREATE VIEW report_spend AS
SELECT
    r.purchase_date,
    r.household_id,
    r.store_id,
    p.category_id,
    rl.product_id,
    rl.quantity,
    rl.line_total_grosze AS amount_grosze
FROM receipt_line rl
JOIN receipt r ON r.id = rl.receipt_id
JOIN product p ON p.id = rl.product_id;
SQL);
    }
}
