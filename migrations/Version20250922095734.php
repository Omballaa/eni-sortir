<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922095734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etats (no_etat SERIAL NOT NULL, libelle VARCHAR(30) NOT NULL, PRIMARY KEY(no_etat))');
        $this->addSql('CREATE TABLE event (id SERIAL NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE inscriptions (no_participant INT NOT NULL, no_sortie INT NOT NULL, date_inscription TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(no_participant, no_sortie))');
        $this->addSql('CREATE INDEX IDX_74E0281C4D89B385 ON inscriptions (no_participant)');
        $this->addSql('CREATE INDEX IDX_74E0281C23031780 ON inscriptions (no_sortie)');
        $this->addSql('CREATE TABLE lieux (no_lieu SERIAL NOT NULL, no_ville INT NOT NULL, nom_lieu VARCHAR(30) NOT NULL, rue VARCHAR(30) DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, PRIMARY KEY(no_lieu))');
        $this->addSql('CREATE INDEX IDX_9E44A8AEA584C510 ON lieux (no_ville)');
        $this->addSql('CREATE TABLE participants (no_participant SERIAL NOT NULL, no_site INT NOT NULL, pseudo VARCHAR(30) NOT NULL, nom VARCHAR(30) NOT NULL, prenom VARCHAR(30) NOT NULL, telephone VARCHAR(15) DEFAULT NULL, mail VARCHAR(50) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, administrateur BOOLEAN NOT NULL, actif BOOLEAN NOT NULL, PRIMARY KEY(no_participant))');
        $this->addSql('CREATE INDEX IDX_716970924D03DBAB ON participants (no_site)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO ON participants (pseudo)');
        $this->addSql('CREATE TABLE sites (no_site SERIAL NOT NULL, nom_site VARCHAR(30) NOT NULL, PRIMARY KEY(no_site))');
        $this->addSql('CREATE TABLE sorties (no_sortie SERIAL NOT NULL, no_etat INT NOT NULL, no_lieu INT NOT NULL, no_organisateur INT NOT NULL, nom VARCHAR(30) NOT NULL, date_heure_debut TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, duree INT DEFAULT NULL, date_limite_inscription DATE NOT NULL, nb_inscriptions_max INT NOT NULL, infos_sortie TEXT DEFAULT NULL, PRIMARY KEY(no_sortie))');
        $this->addSql('CREATE INDEX IDX_488163E8718A252D ON sorties (no_etat)');
        $this->addSql('CREATE INDEX IDX_488163E8B17AF16 ON sorties (no_lieu)');
        $this->addSql('CREATE INDEX IDX_488163E8BC2F2CC1 ON sorties (no_organisateur)');
        $this->addSql('CREATE TABLE villes (no_ville SERIAL NOT NULL, nom_ville VARCHAR(30) NOT NULL, code_postal VARCHAR(10) NOT NULL, PRIMARY KEY(no_ville))');
        $this->addSql('ALTER TABLE inscriptions ADD CONSTRAINT FK_74E0281C4D89B385 FOREIGN KEY (no_participant) REFERENCES participants (no_participant) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inscriptions ADD CONSTRAINT FK_74E0281C23031780 FOREIGN KEY (no_sortie) REFERENCES sorties (no_sortie) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lieux ADD CONSTRAINT FK_9E44A8AEA584C510 FOREIGN KEY (no_ville) REFERENCES villes (no_ville) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participants ADD CONSTRAINT FK_716970924D03DBAB FOREIGN KEY (no_site) REFERENCES sites (no_site) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sorties ADD CONSTRAINT FK_488163E8718A252D FOREIGN KEY (no_etat) REFERENCES etats (no_etat) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sorties ADD CONSTRAINT FK_488163E8B17AF16 FOREIGN KEY (no_lieu) REFERENCES lieux (no_lieu) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sorties ADD CONSTRAINT FK_488163E8BC2F2CC1 FOREIGN KEY (no_organisateur) REFERENCES participants (no_participant) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE inscriptions DROP CONSTRAINT FK_74E0281C4D89B385');
        $this->addSql('ALTER TABLE inscriptions DROP CONSTRAINT FK_74E0281C23031780');
        $this->addSql('ALTER TABLE lieux DROP CONSTRAINT FK_9E44A8AEA584C510');
        $this->addSql('ALTER TABLE participants DROP CONSTRAINT FK_716970924D03DBAB');
        $this->addSql('ALTER TABLE sorties DROP CONSTRAINT FK_488163E8718A252D');
        $this->addSql('ALTER TABLE sorties DROP CONSTRAINT FK_488163E8B17AF16');
        $this->addSql('ALTER TABLE sorties DROP CONSTRAINT FK_488163E8BC2F2CC1');
        $this->addSql('DROP TABLE etats');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE inscriptions');
        $this->addSql('DROP TABLE lieux');
        $this->addSql('DROP TABLE participants');
        $this->addSql('DROP TABLE sites');
        $this->addSql('DROP TABLE sorties');
        $this->addSql('DROP TABLE villes');
    }
}
