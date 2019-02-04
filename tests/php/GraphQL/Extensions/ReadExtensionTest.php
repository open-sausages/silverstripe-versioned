<?php

namespace SilverStripe\Versioned\Tests\GraphQL\Extensions;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Resolvers\ApplyVersionFilters;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\GraphQL\Extensions\ReadExtension;
use SilverStripe\Versioned\GraphQL\Types\VersionedInputType;
use SilverStripe\Versioned\Tests\GraphQL\Fake\Fake;
use SilverStripe\Core\Injector\Injector;

class ReadExtensionTest extends SapphireTest
{

    public static $extra_dataobjects = [
        Fake::class,
    ];

    public function testDoesNotAddVersionedArgByDefault()
    {
        $ext = new ReadExtension();
        $manager = $this->getManager();
        $args = [];
        $ext->updateArgs($args, $manager);
        $this->assertArrayNotHasKey('Versioning', $args);
    }

    public function testAddsVersionedArgWithOptIn()
    {
        $ext = new ReadExtension();
        $ext->setUseVersionedFilter(true);
        $manager = $this->getManager();
        $args = [];
        $ext->updateArgs($args, $manager);
        $this->assertArrayHasKey('Versioning', $args);
    }

    public function testReadExtensionDoesNotApplyFiltersByDefault()
    {
        $mock = $this->getMockBuilder(ApplyVersionFilters::class)
            ->setMethods(['applyToList'])
            ->getMock();
        $mock
            ->expects($this->never())
            ->method('applyToList');

        Injector::inst()->registerService($mock, ApplyVersionFilters::class);

        $manager = $this->getManager();
        $manager->addType(new ObjectType(['name' => StaticSchema::inst()->typeNameForDataObject(Fake::class)]));
        $read = new Read(Fake::class);
        $read->setUsePagination(false);
        $readScaffold = $read->scaffold($manager);
        $this->assertInternalType('callable', $readScaffold['resolve']);
        $readScaffold['resolve'](null, ['Versioning' => true], ['currentUser' => new Member()], new ResolveInfo([]));
    }

    public function testReadExtensionAppliesFiltersWithOptIn()
    {
        $mock = $this->getMockBuilder(ApplyVersionFilters::class)
            ->setMethods(['applyToList'])
            ->getMock();
        $mock
            ->expects($this->once())
            ->method('applyToList');

        Injector::inst()->registerService($mock, ApplyVersionFilters::class);

        $manager = $this->getManager();
        $manager->addType(new ObjectType(['name' => StaticSchema::inst()->typeNameForDataObject(Fake::class)]));
        $read = new Read(Fake::class);
        $read->setUseVersionedFilter(true);
        $read->setUsePagination(false);
        $readScaffold = $read->scaffold($manager);
        $this->assertInternalType('callable', $readScaffold['resolve']);
        $readScaffold['resolve'](null, ['Versioning' => true], ['currentUser' => new Member()], new ResolveInfo([]));
    }

    protected function getManager()
    {
        $manager = new Manager();
        $manager->addType((new VersionedInputType())->toType());

        return $manager;
    }
}
