<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : distributeur, produit, tunnel, tunnel_produit, prospect, visite';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE distributeur (
            id VARCHAR(36) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            nom VARCHAR(100) NOT NULL,
            email VARCHAR(180) NOT NULL,
            mot_de_passe_hash VARCHAR(255) NOT NULL,
            telephone_whatsapp VARCHAR(30) DEFAULT NULL,
            slogan VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX UNIQ_distributeur_email (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE produit (
            id VARCHAR(36) NOT NULL,
            distributeur_id VARCHAR(36) NOT NULL,
            nom VARCHAR(200) NOT NULL,
            categorie VARCHAR(100) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            image_url VARCHAR(500) DEFAULT NULL,
            prix NUMERIC(10, 2) NOT NULL,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX idx_produit_distributeur (distributeur_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE tunnel (
            id VARCHAR(36) NOT NULL,
            distributeur_id VARCHAR(36) NOT NULL,
            nom_tunnel VARCHAR(200) NOT NULL,
            slug_unique VARCHAR(120) NOT NULL,
            titre_page VARCHAR(255) DEFAULT NULL,
            sous_titre VARCHAR(255) DEFAULT NULL,
            texte_cta VARCHAR(100) DEFAULT NULL,
            message_merci LONGTEXT DEFAULT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX UNIQ_tunnel_slug (slug_unique),
            INDEX idx_tunnel_distributeur (distributeur_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE tunnel_produit (
            tunnel_id VARCHAR(36) NOT NULL,
            produit_id VARCHAR(36) NOT NULL,
            ordre_affichage INT NOT NULL DEFAULT 0,
            UNIQUE INDEX uniq_tunnel_produit (tunnel_id, produit_id),
            INDEX IDX_tp_tunnel (tunnel_id),
            INDEX IDX_tp_produit (produit_id),
            PRIMARY KEY(tunnel_id, produit_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE prospect (
            id VARCHAR(36) NOT NULL,
            tunnel_id VARCHAR(36) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            telephone VARCHAR(30) DEFAULT NULL,
            email VARCHAR(180) DEFAULT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT 'nouveau',
            soumis_le DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX idx_prospect_tunnel (tunnel_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE visite (
            id VARCHAR(36) NOT NULL,
            tunnel_id VARCHAR(36) NOT NULL,
            page VARCHAR(100) DEFAULT NULL,
            visite_le DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX idx_visite_tunnel (tunnel_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("ALTER TABLE produit ADD CONSTRAINT FK_produit_distributeur FOREIGN KEY (distributeur_id) REFERENCES distributeur (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tunnel ADD CONSTRAINT FK_tunnel_distributeur FOREIGN KEY (distributeur_id) REFERENCES distributeur (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tunnel_produit ADD CONSTRAINT FK_tp_tunnel FOREIGN KEY (tunnel_id) REFERENCES tunnel (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE tunnel_produit ADD CONSTRAINT FK_tp_produit FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE prospect ADD CONSTRAINT FK_prospect_tunnel FOREIGN KEY (tunnel_id) REFERENCES tunnel (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE visite ADD CONSTRAINT FK_visite_tunnel FOREIGN KEY (tunnel_id) REFERENCES tunnel (id) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE visite DROP FOREIGN KEY FK_visite_tunnel");
        $this->addSql("ALTER TABLE prospect DROP FOREIGN KEY FK_prospect_tunnel");
        $this->addSql("ALTER TABLE tunnel_produit DROP FOREIGN KEY FK_tp_produit");
        $this->addSql("ALTER TABLE tunnel_produit DROP FOREIGN KEY FK_tp_tunnel");
        $this->addSql("ALTER TABLE tunnel DROP FOREIGN KEY FK_tunnel_distributeur");
        $this->addSql("ALTER TABLE produit DROP FOREIGN KEY FK_produit_distributeur");

        $this->addSql("DROP TABLE visite");
        $this->addSql("DROP TABLE prospect");
        $this->addSql("DROP TABLE tunnel_produit");
        $this->addSql("DROP TABLE tunnel");
        $this->addSql("DROP TABLE produit");
        $this->addSql("DROP TABLE distributeur");
    }
}
