<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestModel;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestModelAllowedChild;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestModelCreateOnlyChild;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestModelEditOnlyChild;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestModelRestrictedChild;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest\TestUnrelatedModel;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;

class GridFieldDetailForm_ItemRequestTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestModel::class,
        TestModelAllowedChild::class,
        TestModelCreateOnlyChild::class,
        TestModelEditOnlyChild::class,
        TestModelRestrictedChild::class,
        TestUnrelatedModel::class,
    ];

    public function testItemEditFormThrowsException()
    {
        $gridField = new GridField('dummy', 'dummy', new ArrayList(), new GridFieldConfig_Base());
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);
        $itemRequest = new GridFieldDetailForm_ItemRequest($gridField, new GridFieldDetailForm(), new ArrayData(), new Controller(), '');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot dynamically determine form fields. Pass the fields to GridFieldDetailForm::setFields()'
            . " or implement a getCMSFields() method on $modelClass"
        );

        $itemRequest->ItemEditForm();
    }

    public function testGetGridFieldItemAdjacencies(): void
    {
        $list = new ArrayList([
            new ArrayData(['ID' => 101]),
            new ArrayData(['ID' => 102]),
            new ArrayData(['ID' => 103]),
            new ArrayData(['ID' => 104]),
            new ArrayData(['ID' => 105]),
            new ArrayData(['ID' => 106]),
            new ArrayData(['ID' => 107]),
            new ArrayData(['ID' => 108]),
            new ArrayData(['ID' => 109]),
        ]);
        $gridField = new GridField('dummy', 'dummy', $list, new GridFieldConfig_Base());
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);
        $itemRequest = new GridFieldDetailForm_ItemRequest($gridField, new GridFieldDetailForm(), new ArrayData(), new Controller(), '');
        $request = new HTTPRequest('GET', '/', [
            'gridState-dummy-0' => '{"GridFieldPaginator":{"currentPage":2,"itemsPerPage":3}}'
        ]);
        $itemRequest->setRequest($request);
        // Assert method
        $refl = new ReflectionMethod($itemRequest, 'getGridFieldItemAdjacencies');
        $refl->setAccessible(true);
        $expectedData = [103, 104, 105, 106, 107];
        $this->assertSame($expectedData, $refl->invoke($itemRequest));
        // Assert cache
        $refl = new ReflectionProperty($itemRequest, 'cachedGridFieldItemAdjacencies');
        $refl->setAccessible(true);
        $this->assertSame(
            ['9cffefcf339420d11129b3220eccf141' => $expectedData],
            $refl->getValue($itemRequest)
        );
    }

    public function provideSaveFormRejectsUnauthorisedClassNameChange(): array
    {
        return [
            'class outside the list hierarchy' => [
                'newClassName' => TestUnrelatedModel::class,
                'isNewRecord' => false,
            ],
            'in-hierarchy class the user cannot create/edit' => [
                'newClassName' => TestModelRestrictedChild::class,
                'isNewRecord' => false,
            ],
            'existing record converted to a class the user cannot create' => [
                'newClassName' => TestModelEditOnlyChild::class,
                'isNewRecord' => false,
            ],
            'new record created as a class the user cannot edit' => [
                'newClassName' => TestModelCreateOnlyChild::class,
                'isNewRecord' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideSaveFormRejectsUnauthorisedClassNameChange
     */
    public function testSaveFormRejectsUnauthorisedClassNameChange(string $newClassName, bool $isNewRecord): void
    {
        $record = new TestModel();
        $record->Name = 'Original';
        if (!$isNewRecord) {
            $record->write();
        }
        $recordID = $record->ID;

        $list = TestModel::get();
        $gridField = new GridField('dummy', 'dummy', $list, new GridFieldConfig_Base());
        $gridField->setModelClass(TestModel::class);
        $itemRequest = new GridFieldDetailForm_ItemRequest(
            $gridField,
            new GridFieldDetailForm(),
            $record,
            new Controller(),
            ''
        );

        $form = new Form($itemRequest, 'ItemEditForm', new FieldList(new TextField('Name')), new FieldList());

        // Raw request data injects a ClassName that is never a saveable form field.
        $data = ['Name' => 'Hacked', 'ClassName' => $newClassName];

        $refl = new ReflectionMethod($itemRequest, 'saveFormIntoRecord');
        $refl->setAccessible(true);

        $threw = false;
        try {
            $refl->invoke($itemRequest, $data, $form);
        } catch (HTTPResponse_Exception $e) {
            $threw = true;
            $this->assertSame(403, $e->getResponse()->getStatusCode());
        }

        $this->assertTrue($threw);

        if ($isNewRecord) {
            // A rejected conversion must not persist anything at all.
            $this->assertCount(0, TestModel::get());
            return;
        }

        // The persisted record must NOT have been converted to the injected class.
        $stored = TestModel::get()->byID($recordID);
        $this->assertInstanceOf(TestModel::class, $stored);
        $this->assertSame(TestModel::class, $stored->ClassName);
        $this->assertSame('Original', $stored->Name);
    }

    public function provideSaveFormAllowsPermittedInHierarchyClassNameChange(): array
    {
        return [
            'existing record' => ['isNewRecord' => false],
            'new record' => ['isNewRecord' => true],
        ];
    }

    /**
     * Sanity check: a legitimate in-hierarchy conversion the user is permitted to make
     * still works, so the fix does not weaken existing functionality.
     * @dataProvider provideSaveFormAllowsPermittedInHierarchyClassNameChange
     */
    public function testSaveFormAllowsPermittedInHierarchyClassNameChange(bool $isNewRecord): void
    {
        $record = new TestModel();
        $record->Name = 'Original';
        if (!$isNewRecord) {
            $record->write();
        }

        $list = TestModel::get();
        $gridField = new GridField('dummy', 'dummy', $list, new GridFieldConfig_Base());
        $gridField->setModelClass(TestModel::class);
        $itemRequest = new GridFieldDetailForm_ItemRequest(
            $gridField,
            new GridFieldDetailForm(),
            $record,
            new Controller(),
            ''
        );

        $form = new Form($itemRequest, 'ItemEditForm', new FieldList(new TextField('Name')), new FieldList());

        $data = ['Name' => 'Updated', 'ClassName' => TestModelAllowedChild::class];
        $form->loadDataFrom($data);

        $refl = new ReflectionMethod($itemRequest, 'saveFormIntoRecord');
        $refl->setAccessible(true);
        $refl->invoke($itemRequest, $data, $form);

        $storedList = TestModel::get();
        $this->assertCount(1, $storedList);
        $stored = $storedList->first();
        $this->assertInstanceOf(TestModelAllowedChild::class, $stored);
        $this->assertSame(TestModelAllowedChild::class, $stored->ClassName);
        $this->assertSame('Updated', $stored->Name);
    }
}
