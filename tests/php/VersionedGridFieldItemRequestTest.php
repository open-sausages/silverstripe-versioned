<?php

namespace SilverStripe\Versioned\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Tests\VersionedGridFieldItemRequestTest\UnversionedObject;
use SilverStripe\Versioned\Tests\VersionedGridFieldItemRequestTest\UnversionedOwner;
use SilverStripe\Versioned\Tests\VersionedGridFieldItemRequestTest\VersionedObject;
use SilverStripe\Versioned\Tests\VersionedGridFieldTest\TestController;
use SilverStripe\Versioned\VersionedGridFieldItemRequest;

class VersionedGridFieldItemRequestTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        VersionedObject::class,
        UnversionedOwner::class,
        UnversionedObject::class,
    ];

    public function testItSetsPublishedButtonsForVersionedOwners()
    {
        $itemRequest = $this->createItemRequestForObject(new VersionedObject());
        $this->logInWithPermission('ADMIN');
        $form = $itemRequest->ItemEditForm();
        $actions = $form->Actions();
        $this->assertInstanceOf(FormAction::class, $actions->fieldByName('action_doPublish'));
    }

    public function testActionsUnversionedOwner()
    {
        $itemRequest = $this->createItemRequestForObject(new UnversionedOwner());
        $this->logInWithPermission('ADMIN');
        $form = $itemRequest->ItemEditForm();
        $actions = $form->Actions();

        // No publish action
        $this->assertNull($actions->fieldByName('action_doPublish'));
        $this->assertInstanceOf(FormAction::class, $actions->fieldByName('action_doSave'));

        /** @var LiteralField $warningField */
        $warningField = $actions->fieldByName('warning');
        $this->assertInstanceOf(LiteralField::class, $warningField);
        $this->assertRegExp('/will be published/', $warningField->getContent());
    }

    /**
     * A save on a versioned object won't trigger a publish
     */
    public function testVersionedObjectsNotPublished()
    {
        $newObject = new VersionedObject();
        $itemRequest = $this->createItemRequestForObject($newObject);
        $this->logInWithPermission('ADMIN');
        $form = $itemRequest->ItemEditForm();
        $form->loadDataFrom($data = ['Title' => 'New Object']);
        $itemRequest->doSave($data, $form);

        $this->assertTrue($newObject->isInDB());
        $this->assertTrue($newObject->isOnDraftOnly());
    }

    /**
     * A save on an unversioned object triggers publishing
     */
    public function testUnversionedObjectsPublishChildren()
    {
        $newChild = new VersionedObject();
        $newChild->Title = 'New child versioned';
        $newChild->write();
        $newObject = new UnversionedOwner();
        $newObject->RelatedID = $newChild->ID;
        $newObject->write();

        // Update this
        $itemRequest = $this->createItemRequestForObject($newObject);
        $this->logInWithPermission('ADMIN');
        $form = $itemRequest->ItemEditForm();
        $form->loadDataFrom($newObject);
        $itemRequest->doSave($newObject->toMap(), $form);

        // Nested child is published
        $this->assertTrue($newChild->isPublished());
    }

    protected function createItemRequestForObject(DataObject $obj)
    {
        $controller = new TestController();
        $parentForm = new Form($controller, 'Form', new FieldList(), new FieldList());
        return new VersionedGridFieldItemRequest(
            GridField::create('test', 'test', new ArrayList())
                ->setForm($parentForm),
            new GridFieldDetailForm(),
            $obj,
            $controller,
            'test'
        );
    }
}