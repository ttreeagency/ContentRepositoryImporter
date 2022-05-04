<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add RecordMapping entity
 */
class Version20150319132414 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("CREATE TABLE ttree_contentrepositoryimporter_domain_model_recordmapping (persistence_object_identifier VARCHAR(40) NOT NULL, creationdate DATETIME NOT NULL, modificationdate DATETIME DEFAULT NULL, importerclassname VARCHAR(255) NOT NULL, importerclassnamehash VARCHAR(32) NOT NULL, externalidentifier VARCHAR(255) NOT NULL, externalrelativeuri VARCHAR(255) DEFAULT NULL, nodeidentifier VARCHAR(255) NOT NULL, nodepath VARCHAR(4000) NOT NULL, nodepathhash VARCHAR(32) NOT NULL, INDEX importerclassnamehash_externalidentifier (importerclassnamehash, externalidentifier), INDEX nodepathhash (nodepathhash), INDEX nodeidentifier (nodeidentifier), UNIQUE INDEX UNIQ_8C1932AD8FC282F11EE98D63 (importerclassnamehash, externalidentifier), PRIMARY KEY(persistence_object_identifier))");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("DROP TABLE ttree_contentrepositoryimporter_domain_model_recordmapping");
    }
}
