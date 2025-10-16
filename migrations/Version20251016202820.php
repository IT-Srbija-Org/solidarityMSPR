<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016202820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tenant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE damaged_educator ADD tenant_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE damaged_educator ADD CONSTRAINT FK_F4611DF39033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F4611DF39033212A ON damaged_educator (tenant_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_donor ADD tenant_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_donor ADD CONSTRAINT FK_3530BBCF9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3530BBCF9033212A ON user_donor (tenant_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE damaged_educator DROP FOREIGN KEY FK_F4611DF39033212A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_donor DROP FOREIGN KEY FK_3530BBCF9033212A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tenant
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3530BBCF9033212A ON user_donor
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_donor DROP tenant_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F4611DF39033212A ON damaged_educator
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE damaged_educator DROP tenant_id
        SQL);
    }
}
