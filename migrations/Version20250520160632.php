<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250520160632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, scheduler_name VARCHAR(255) NOT NULL, scheduler_email VARCHAR(255) NOT NULL, start_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', end_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE appointment_participant (appointment_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_AC57098DE5B533F9 (appointment_id), INDEX IDX_AC57098D9D1C3019 (participant_id), PRIMARY KEY(appointment_id, participant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE participant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_participant ADD CONSTRAINT FK_AC57098DE5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_participant ADD CONSTRAINT FK_AC57098D9D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_participant DROP FOREIGN KEY FK_AC57098DE5B533F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_participant DROP FOREIGN KEY FK_AC57098D9D1C3019
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE appointment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE appointment_participant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE participant
        SQL);
    }
}
