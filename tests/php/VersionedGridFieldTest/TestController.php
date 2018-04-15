<?php

namespace SilverStripe\Versioned\Tests\VersionedGridFieldTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfigRelationEditor;
use SilverStripe\Versioned\Tests\VersionedTest\TestObject;
use SilverStripe\Versioned\Versioned;

/**
 * @skipUpgrade
 */
class TestController extends Controller implements TestOnly
{
    protected function init()
    {
        parent::init();
        Versioned::set_stage(Versioned::DRAFT);
    }

    public function __construct()
    {
        parent::__construct();
        if (Controller::has_curr()) {
            $this->setRequest(Controller::curr()->getRequest());
        }
    }

    public function Link($action = null)
    {
        return Controller::join_links('VersionedGridFieldTest_Controller', $action, '/');
    }

    private static $allowed_actions = ['Form'];

    protected $template = 'BlankPage';

    public function Form()
    {
        $objects = TestObject::get()
            ->sort('"VersionedTest_DataObject"."ID" ASC');
        $field = new GridField('testfield', 'testfield', $objects, GridFieldConfigRelationEditor::create());
        return new Form($this, 'Form', new FieldList($field), new FieldList());
    }
}
