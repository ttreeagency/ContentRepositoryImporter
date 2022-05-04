<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce externalimportidentifier property to Import model
 */
class Version20160523202439 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string  {
        return 'Introduce externalimportidentifier property to Import model';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE ttree_contentrepositoryimporter_domain_model_import ADD externalimportidentifier VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE ttree_contentrepositoryimporter_domain_model_import DROP externalimportidentifier');
    }
}
