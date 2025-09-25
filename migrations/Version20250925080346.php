<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925080346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE event_id_seq CASCADE');
        $this->addSql('CREATE TABLE groupe_membre (id SERIAL NOT NULL, groupe_id INT NOT NULL, participant_id INT NOT NULL, date_ajout TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_retrait TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, actif BOOLEAN NOT NULL, est_admin BOOLEAN NOT NULL, notifications BOOLEAN NOT NULL, derniere_visite TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9D8A07137A45358C ON groupe_membre (groupe_id)');
        $this->addSql('CREATE INDEX IDX_9D8A07139D1C3019 ON groupe_membre (participant_id)');
        $this->addSql('CREATE TABLE groupe_message (id SERIAL NOT NULL, sortie_id INT DEFAULT NULL, createur_id INT NOT NULL, nom VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, date_creation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, est_actif BOOLEAN NOT NULL, type VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_70DAD731CC72D953 ON groupe_message (sortie_id)');
        $this->addSql('CREATE INDEX IDX_70DAD73173A201E5 ON groupe_message (createur_id)');
        $this->addSql('CREATE TABLE message (id SERIAL NOT NULL, expediteur_id INT NOT NULL, groupe_id INT DEFAULT NULL, destinataire_id INT DEFAULT NULL, contenu TEXT NOT NULL, date_envoi TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, est_systeme BOOLEAN NOT NULL, type_systeme VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6BD307F10335F61 ON message (expediteur_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F7A45358C ON message (groupe_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FA4F84F6E ON message (destinataire_id)');
        $this->addSql('CREATE TABLE message_status (id SERIAL NOT NULL, message_id INT NOT NULL, participant_id INT NOT NULL, lu BOOLEAN NOT NULL, date_lecture TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_creation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4C27F813537A1329 ON message_status (message_id)');
        $this->addSql('CREATE INDEX IDX_4C27F8139D1C3019 ON message_status (participant_id)');
        $this->addSql('ALTER TABLE groupe_membre ADD CONSTRAINT FK_9D8A07137A45358C FOREIGN KEY (groupe_id) REFERENCES groupe_message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groupe_membre ADD CONSTRAINT FK_9D8A07139D1C3019 FOREIGN KEY (participant_id) REFERENCES participants (no_participant) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groupe_message ADD CONSTRAINT FK_70DAD731CC72D953 FOREIGN KEY (sortie_id) REFERENCES sorties (no_sortie) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groupe_message ADD CONSTRAINT FK_70DAD73173A201E5 FOREIGN KEY (createur_id) REFERENCES participants (no_participant) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES participants (no_participant) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F7A45358C FOREIGN KEY (groupe_id) REFERENCES groupe_message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES participants (no_participant) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_status ADD CONSTRAINT FK_4C27F813537A1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_status ADD CONSTRAINT FK_4C27F8139D1C3019 FOREIGN KEY (participant_id) REFERENCES participants (no_participant) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE event');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE event_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE event (id SERIAL NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE groupe_membre DROP CONSTRAINT FK_9D8A07137A45358C');
        $this->addSql('ALTER TABLE groupe_membre DROP CONSTRAINT FK_9D8A07139D1C3019');
        $this->addSql('ALTER TABLE groupe_message DROP CONSTRAINT FK_70DAD731CC72D953');
        $this->addSql('ALTER TABLE groupe_message DROP CONSTRAINT FK_70DAD73173A201E5');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F7A45358C');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FA4F84F6E');
        $this->addSql('ALTER TABLE message_status DROP CONSTRAINT FK_4C27F813537A1329');
        $this->addSql('ALTER TABLE message_status DROP CONSTRAINT FK_4C27F8139D1C3019');
        $this->addSql('DROP TABLE groupe_membre');
        $this->addSql('DROP TABLE groupe_message');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_status');
    }
}
