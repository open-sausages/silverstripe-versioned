<?php
namespace SilverStripe\Versioned\Tests\GraphQL\Extensions;

use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\Versioned\GraphQL\Types\VersionedStage;
use SilverStripe\Versioned\Tests\GraphQL\Fake\Fake;
use SilverStripe\Versioned\Tests\VersionedTest\UnversionedWithField;
use SilverStripe\Versioned\Versioned;

class DataObjectScaffolderExtensionTest extends SapphireTest
{
    public static $extra_dataobjects = [
        Fake::class,
    ];

    public function testDataObjectScaffolderDoesNotAddVersionedFieldsByDefault()
    {
        $manager = new Manager();
        $manager->addType((new VersionedStage())->toType());
        $scaffolder = new DataObjectScaffolder(Fake::class);
        $scaffolder->addFields(['Name', 'Title']);
        $scaffolder->addToManager($manager);
        $typeName = $scaffolder->getTypeName();

        $type = $manager->getType($typeName);
        $this->assertInstanceOf(ObjectType::class, $type);
        $fields = $type->config['fields']();
        $this->assertArrayNotHasKey('Version', $fields);
        $this->assertArrayNotHasKey('Versions', $fields);
    }

    public function testDataObjectScaffolderAddsVersionedFieldsWithOptIn()
    {
        $manager = new Manager();
        $manager->addType((new VersionedStage())->toType());
        $scaffolder = new DataObjectScaffolder(Fake::class);
        $scaffolder->setUseVersionedFilter(true);
        $scaffolder->setUseVersionedMetadata(true);
        $scaffolder->addFields(['Name', 'Title']);
        $scaffolder->addToManager($manager);
        $typeName = $scaffolder->getTypeName();

        $type = $manager->getType($typeName);
        $this->assertInstanceOf(ObjectType::class, $type);
        $fields = $type->config['fields']();
        $this->assertArrayHasKey('Version', $fields);
        $this->assertArrayHasKey('Versions', $fields);
        $this->assertInstanceOf(IntType::class, $fields['Version']['type']);
        $this->assertInstanceOf(ObjectType::class, $fields['Versions']['type']);
    }

    public function testDataObjectScaffolderDoesntAddVersionedFieldsToUnversionedObjectsWithOptIn()
    {
        Fake::remove_extension(Versioned::class);
        $manager = new Manager();
        $manager->addType((new VersionedStage())->toType());
        $scaffolder = new DataObjectScaffolder(UnversionedWithField::class);
        $scaffolder->setUseVersionedFilter(true);
        $scaffolder->setUseVersionedMetadata(true);
        $scaffolder->addToManager($manager);
        $typeName = $scaffolder->getTypeName();

        $type = $manager->getType($typeName);
        $this->assertInstanceOf(ObjectType::class, $type);
        $fields = $type->config['fields']();
        $this->assertArrayNotHasKey('Version', $fields);
        $this->assertArrayNotHasKey('Versions', $fields);
    }
}
