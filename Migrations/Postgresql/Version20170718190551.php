<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add tables to store content repository importer data
 */
class Version20170718190551 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Add tables to store content repository importer data';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');
        
        $this->addSql('CREATE TABLE ttree_contentrepositoryimporter_domain_model_import (persistence_object_identifier VARCHAR(40) NOT NULL, starttime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, endtime TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, externalimportidentifier VARCHAR(255) DEFAULT NULL, PRIMARY KEY(persistence_object_identifier))');
        $this->addSql('CREATE TABLE ttree_contentrepositoryimporter_domain_model_recordmapping (persistence_object_identifier VARCHAR(40) NOT NULL, creationdate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, modificationdate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, importerclassname VARCHAR(255) NOT NULL, importerclassnamehash VARCHAR(32) NOT NULL, externalidentifier VARCHAR(255) NOT NULL, externalrelativeuri VARCHAR(255) DEFAULT NULL, nodeidentifier VARCHAR(255) NOT NULL, nodepath VARCHAR(4000) NOT NULL, nodepathhash VARCHAR(32) NOT NULL, PRIMARY KEY(persistence_object_identifier))');
        $this->addSql('CREATE INDEX nodepathhash ON ttree_contentrepositoryimporter_domain_model_recordmapping (nodepathhash)');
        $this->addSql('CREATE INDEX nodeidentifier ON ttree_contentrepositoryimporter_domain_model_recordmapping (nodeidentifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C1932AD8FC282F11EE98D63 ON ttree_contentrepositoryimporter_domain_model_recordmapping (importerclassnamehash, externalidentifier)');
        $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event ADD externalidentifier VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');
        
        $this->addSql('DROP TABLE ttree_contentrepositoryimporter_domain_model_import');
        $this->addSql('DROP TABLE ttree_contentrepositoryimporter_domain_model_recordmapping');
        $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event DROP externalidentifier');
    }
}
