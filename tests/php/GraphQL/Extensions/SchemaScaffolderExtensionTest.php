<?php

namespace SilverStripe\Versioned\Tests\GraphQL\Extensions;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\GraphQL\Types\VersionedStage;
use SilverStripe\Versioned\Tests\VersionedTest\ChangeSetFake;
use SilverStripe\Versioned\Tests\GraphQL\Fake\Fake;
use SilverStripe\Versioned\Tests\VersionedTest\UnversionedWithField;

class SchemaScaffolderExtensionTest extends SapphireTest
{

    public static $extra_dataobjects = [
        Fake::class,
    ];

    public function testMemberTypeNotCreatedByDefault()
    {
        $manager = new Manager();
        $manager->addType((new VersionedStage())->toType());
        $memberType = StaticSchema::inst()->typeNameForDataObject(Member::class);
        $this->assertFalse($manager->hasType($memberType));

        $scaffolder = new SchemaScaffolder(); // not setting setUseVersionedFilter(true)
        $scaffolder->type(Fake::class);
        $scaffolder->addToManager($manager);

        $this->assertFalse($manager->hasType($memberType));
    }

    public function testMemberTypeCreatedWithOptInForVersionedObjects()
    {
        $manager = new Manager();
        $manager->addType((new VersionedStage())->toType());
        $memberType = StaticSchema::inst()->typeNameForDataObject(Member::class);
        $this->assertFalse($manager->hasType($memberType));

        $scaffolder = new SchemaScaffolder();
        $scaffolder->setUseVersionedMetadata(true);
        $scaffolder->setUseVersionedFilter(true);
        $scaffolder->type(Fake::class);
        $scaffolder->addToManager($manager);

        $this->assertTrue($manager->hasType($memberType));
        $this->assertInstanceOf(ObjectType::class, $manager->getType($memberType));
    }

    public function testMemberTypeNotCreatedWithOptInForUnversionedObjects()
    {
        $memberType = StaticSchema::inst()->typeNameForDataObject(Member::class);
        $manager = new Manager();
        $this->assertFalse($manager->hasType($memberType));

        $scaffolder = new SchemaScaffolder();
        $scaffolder->setUseVersionedMetadata(true);
        $scaffolder->setUseVersionedFilter(true);
        $scaffolder->type(UnversionedWithField::class);
        $scaffolder->addToManager($manager);
        $this->assertFalse($manager->hasType($memberType));
    }
}
