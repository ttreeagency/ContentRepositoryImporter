<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add Import entity and add ExternalIdentifier property in EventLog
 */
class Version20150317235257 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD externalidentifier VARCHAR(255) DEFAULT NULL");
        $this->addSql("CREATE TABLE ttree_contentrepositoryimporter_domain_model_import (persistence_object_identifier VARCHAR(40) NOT NULL, start DATETIME NOT NULL, end DATETIME DEFAULT NULL, PRIMARY KEY(persistence_object_identifier))");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP externalidentifier");
        $this->addSql("DROP TABLE ttree_contentrepositoryimporter_domain_model_import");
    }
}
