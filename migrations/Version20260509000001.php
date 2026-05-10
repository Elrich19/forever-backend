<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Produit : ajoute image_urls (JSON, multi-images) et migre image_url en première entrée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE produit ADD image_urls JSON DEFAULT NULL");
        // Migration des données : image_url → imageUrls[0]
        $this->addSql("UPDATE produit SET image_urls = JSON_ARRAY(image_url) WHERE image_url IS NOT NULL AND image_url <> ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE produit DROP COLUMN image_urls");
    }
}
