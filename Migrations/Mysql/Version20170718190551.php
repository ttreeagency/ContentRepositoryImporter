<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adapt column naming to support PostgreSQL
 */
class Version20170718190551 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Adapt column naming to support PostgreSQL';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');
        
        $this->addSql('ALTER TABLE ttree_contentrepositoryimporter_domain_model_import CHANGE `start` `starttime` DATETIME NOT NULL, CHANGE `end` `endtime` DATETIME DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE ttree_contentrepositoryimporter_domain_model_import CHANGE `starttime` `start` DATETIME NOT NULL, CHANGE `endtime` `end` DATETIME DEFAULT NULL');
    }
}
