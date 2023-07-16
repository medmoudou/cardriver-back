<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230618161247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE individual DROP FOREIGN KEY FK_8793FC179D86650F');
        $this->addSql('DROP INDEX UNIQ_8793FC179D86650F ON individual');
        $this->addSql('ALTER TABLE individual CHANGE user_id_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE individual ADD CONSTRAINT FK_8793FC17A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8793FC17A76ED395 ON individual (user_id)');
        $this->addSql('ALTER TABLE professional DROP FOREIGN KEY FK_B3B573AA9D86650F');
        $this->addSql('DROP INDEX UNIQ_B3B573AA9D86650F ON professional');
        $this->addSql('ALTER TABLE professional CHANGE user_id_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE professional ADD CONSTRAINT FK_B3B573AAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3B573AAA76ED395 ON professional (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE individual DROP FOREIGN KEY FK_8793FC17A76ED395');
        $this->addSql('DROP INDEX UNIQ_8793FC17A76ED395 ON individual');
        $this->addSql('ALTER TABLE individual CHANGE user_id user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE individual ADD CONSTRAINT FK_8793FC179D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8793FC179D86650F ON individual (user_id_id)');
        $this->addSql('ALTER TABLE professional DROP FOREIGN KEY FK_B3B573AAA76ED395');
        $this->addSql('DROP INDEX UNIQ_B3B573AAA76ED395 ON professional');
        $this->addSql('ALTER TABLE professional CHANGE user_id user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE professional ADD CONSTRAINT FK_B3B573AA9D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3B573AA9D86650F ON professional (user_id_id)');
    }
}
