<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prospect : ajoute produits_interesse (snapshot JSON des produits sélectionnés au moment de la soumission)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE prospect ADD produits_interesse JSON DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE prospect DROP COLUMN produits_interesse");
    }
}
