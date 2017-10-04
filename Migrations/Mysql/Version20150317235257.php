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

        /**
         * We need to check what the name of table flow accounts table is. If you install flow, run migrations, then add usermananagement,
         * then run the UserManagement migrations, the name will will be neos_flow_...
         * However, if you install everything in one go and run migrations then, the order will be different because this migration
         * comes before the Flow migration where the table is renamed (Version20161124185047). So we need to check which of these two
         * tables exist and set the FK relation accordingly.
         **/
        if ($this->sm->tablesExist('neos_neos_eventlog_domain_model_event')) {
            // "neos_" table is there - this means flow migrations have already been run.
            $this->addSql("ALTER TABLE neos_neos_eventlog_domain_model_event ADD externalidentifier VARCHAR(255) DEFAULT NULL");
        } else if ($this->sm->tablesExist('typo3_neos_eventlog_domain_model_event')) {
            // Flow migrations have not been run fully yet, table still has the old name.
            $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD externalidentifier VARCHAR(255) DEFAULT NULL");
        }
        $this->addSql("CREATE TABLE ttree_contentrepositoryimporter_domain_model_import (persistence_object_identifier VARCHAR(40) NOT NULL, start DATETIME NOT NULL, end DATETIME DEFAULT NULL, PRIMARY KEY(persistence_object_identifier))");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE neos_neos_eventlog_domain_model_event DROP externalidentifier");
        $this->addSql("DROP TABLE ttree_contentrepositoryimporter_domain_model_import");
    }
}
